<?php 

namespace App\Service;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base class for extended search functionalities.
 * 
 * @author Ben Brooksnieder
 */
abstract class ExtendedSearch {

    public static string $regex_single_match = '/(!?\w+):((?:(?:(["\'])[\w <>()\-\.\/,=!üÜöÖäÄ]+)\3)|(?:[\w<>()\-\.\/,=!üÜöÖäÄ]+))/';
    public static string $regex_multiple_match = '/(!?\w+):\[((?:(["\']?)[\w <>()\-\.\/,=!üÜöÖäÄ]+\3\|)*(["\']?)[\w <>()\-\.\/,=!üÜöÖäÄ]+\4)\]/';

    protected array $params = [];
    protected array $errors = []; 
    protected Expr $exprBuilder;

    public function __construct(
        protected EntityManagerInterface $entityManager, 
        protected TranslatorInterface $translator)
    {}
    
    /**
     * Return simple text based search on selected columns.
     * @param string $query
     * @return Query
     */
    abstract protected function simpleSearchQuery(string $query): Query;

    /**
     * Matches $key to any known search query keyword and returns fitting subquery.
     * @param string $key                                     Search keyword
     * @param array{key: string, neg: bool, val: array} $data Argument data
     * @return Andx|Comparison|Func|Orx|string|null
     */
    abstract protected function matchQueryKey(string $key, array $data): Andx|Comparison|Func|Orx|string|null;

    /**
     * Return QueryBuilder for search instance, with necessary table joins.
     * @return QueryBuilder
     */
    abstract protected function getQueryBuilderWithTables(): QueryBuilder;

    /**
     * Generates either simple or complex search query based on input query.
     * @param string $query Input query
     * @return Query|null Search query or `null` on failure
     */
    public function generateSearchQuery(string $query): ?Query
    {
        if ($this->isExtendedQuery($query)) {            
            return $this->parseQuery($query);
        }
        
        return $this->simpleSearchQuery($query);
    }

    /**
     * Determines whether given query should be processed as a simple or extended search.
     * @todo make more sophisticated
     * @param string $query Query to check.
     * @return bool
     */
    public function isExtendedQuery(string $query): bool
    {
        $exlusiveChars = '<>[]:|';
        if (strpbrk($query, $exlusiveChars)){
            return true;
        }
        
        #TODO make more sophisticated
        return false;
    }

    protected function parseQuery(string $query): Query|null 
    {
        $this->params = [];
        $this->errors = [];

        // instance of expression builder
        $this->exprBuilder = $this->entityManager->getExpressionBuilder();
        
        // split query in segments
        $orSegments = explode('||', $query);
        // expression for segments 
        $segmentExprs = [];

        // iterate over all segments divided by '||'
        foreach ($orSegments as $segment) {
            // parse segment for key value pairs
            $subqueries = $this->getQueryValues($segment);
            if(\count($subqueries) == 0){
                $this->addError('warning', 'es.error.segment.empty', ['segment' => $segment]);
                continue;
            }
            $subexprs = [];
            
            // iterate over all subqueries in segment
            foreach ($subqueries as $q) {
                // match each expressions in sub query to sql query string
                $parsedExpr = $this->matchQueryKey($q['key'], $q);
                if ($parsedExpr) {
                    $subexprs[] = $parsedExpr;
                }
            }

            // skip if no valid sub expressions were found
            if(\count($subexprs) == 0) {
                $this->addError('warning', 'es.error.segment.invalid', ['segment' => $segment]);
                continue;
            }

            // if only one sub expression, return that one, else join with AND
            $segmentExprs[] = (\count($subexprs) == 1)
                ? $subexprs[0]
                : $this->exprBuilder->andX(array_shift($subexprs), ...$subexprs);
        }

        // return if no valid queries
        if(\count($segmentExprs) == 0){
            $this->addError('danger', 'es.error.query.empty');
            return null;
        }

        // if only one segment, return that segment, else join with OR
        $exprs = (\count($segmentExprs) == 1) 
            ? $segmentExprs[0]
            : $this->exprBuilder->orX(array_shift($segmentExprs), ...$segmentExprs);

        // get query builder and join tables
        $builder = $this->getQueryBuilderWithTables();

        // place query
        $builder->where($exprs);
        
        if (\count($this->params) !== 0) {
            // place parameters
            $params = array_map(fn($elem, $i) => new Parameter($i, $elem), 
                $this->params, 
                range(0, \count($this->params)-1)
            );
            $params = new ArrayCollection($params);
            $builder->setParameters($params);
        }

        return $builder->getQuery();
    }
    
    //
    // ========= QUERY BUILDER METHODS =========
    //

    /**
     * Creates SQL expression for string search.
     * @param string $identifier    Identifier of field
     * @param bool $neg             Whether to negate query
     * @param array $values         Search values, needs to match at least one
     * @return Comparison|Orx
     */
    protected function stringQuery(string $identifier, bool $neg, array $values): Comparison|Andx|Orx
    {
        // add "%" to match any characters
        $values = array_map(fn ($val) => "%$val%", $values);

        $exprs = [];
        // add like expressions for all values
        foreach ($values as $key => $val) {
            $exprs[] = $neg 
                ? $this->exprBuilder->notLike($identifier, $this->addParam($val))
                : $this->exprBuilder->like($identifier, $this->addParam($val));
        }

        // simple like query
        if (1 == \count($values)) {
            return $exprs[0];
        }
        else {
            if ($neg){
                return $this->exprBuilder->andX(array_shift($exprs), ...$exprs);
            }   
            else {
                return $this->exprBuilder->orX(array_shift($exprs), ...$exprs);
            }
        }
    }

    /**
     * Creates SQL expression for equality of one or more possible values.
     * @param string $identifier    Identifier of field
     * @param bool $neg             Whether to negate query
     * @param array $values         Search values, needs to match at least one
     * @return Comparison|Func
     */
    protected function equalQuery(string $identifier, bool $neg, array $values): Comparison|Func
    {
        if (\is_string($values))
            $values = [$values];

        // if only one value present, to simple comparison
        if (\count($values) == 1){
            if($neg){
                return $this->exprBuilder->neq($identifier, $this->addParam($values[0]));
            }
            else {
                return $this->exprBuilder->eq($identifier, $this->addParam($values[0]));
            }
        }

        // else do IN query
        if($neg){
            return $this->exprBuilder->notIn($identifier, $this->addParam($values));
        }
        else {
            return $this->exprBuilder->in($identifier, $this->addParam($values));
        }
    }

    /**
     * Creates SQL expression for null-checking
     * @param string $identifier Identifier of field
     * @param bool $exists       If field should be set or not
     * @return string 
     */
    protected function existenceQuery(string $identifier, bool $exists): string
    {
        if($exists)    
            return $this->exprBuilder->isNotNull($identifier);
        else
            return $this->exprBuilder->isNull($identifier);
    }

    /**
     * Date query, supports ranges with > and < modifier.
     * 
     * Supported date formats:
     * 
     *  - `dd.mm.yyyy`
     *  - `dd.mm.yy`
     *  - `yyyy-mm-dd`
     *  - `mm/dd/yyyy`
     *  - `mm/dd/yy`
     * 
     * @param string $identifier Identifier of field
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Func|Orx
     */
    protected function dateQuery(string $identifier, bool $neg, array $values): Comparison|Func|Orx
    {
        $pattern = [
            '/(?<operator>[<>]|<=|>=)?(?<day>\d{2})\.(?<month>\d{2})\.(?<year>\d{4}|\d{2})/',
            '/(?<operator>[<>]|<=|>=)?(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})/',
            '/(?<operator>[<>]|<=|>=)?(?<month>\d{2})\/(?<day>\d{2})\/(?<year>\d{4}|\d{2})/',
        ];
        
        // try to parse dates
        $values = array_map(function($val) use ($pattern){
            foreach ($pattern as $pat) {
                $matches = [];
                if(preg_match($pat, $val, $matches)){
                    // return formatted date
                    return [
                        'operator' => $matches['operator'], 
                        'date' => str_pad($matches['year'], 4, '20', STR_PAD_LEFT)."-{$matches['month']}-{$matches['day']}"
                    ];
                }
            }
                        
            return false;
        }, $values);

        // filter bad and invalid dates
        $values = array_filter($values, function($val) {
            if(!$val) {
                return false;
            }
            
            return (bool) date_create($val['date']);
        });

        // parse date queries
        $exprs = array_map(function($match) use ($identifier)  {
            $param = $this->addParam($match['date']);
            return match ($match['operator']) {
                '' => $this->exprBuilder->eq("DATE_DIFF($identifier, $param)", 0),
                '<' => $this->exprBuilder->lt("DATE_DIFF($identifier, $param)", 0),
                '<=' => $this->exprBuilder->lte("DATE_DIFF($identifier, $param)", 0),
                '>' => $this->exprBuilder->gt("DATE_DIFF($identifier, $param)", 0),
                '>=' => $this->exprBuilder->gte("DATE_DIFF($identifier, $param)", 0),
            };
        }, $values);

        // join expressions with OR
        $expr = (\count($exprs) == 1) 
            ? $exprs[0]
            : $this->exprBuilder->orX(array_shift($exprs), ...$exprs);
        
        // optionally negate query
        if($neg) {
            return $this->exprBuilder->not($expr);
        }
        else {
            return $expr;
        }
    }
    
    /**
     * Adds parameter to parameter list and returns parameter binding.
     * @param mixed $parameter Parameter to add
     * @return string Reference in the form of "?x"
     */
    protected function addParam($parameter): string
    {
        $id = array_push($this->params, $parameter) - 1;
        return "?$id";
    }

    //
    // ========= PARSING METHODS =========
    //

    /**
     * Return all key value pairs of query.
     * @param string $query
     * @return array<array{key: string, neg: bool, val: array}>
     */
    protected function getQueryValues(string $query): array
    {
        $res = [];
        // get single key values
        $matches = $this->matchKeySingleValue($query);
        foreach ($matches as $match) {
            $res[] = [
                'neg' => str_starts_with($match[1], '!'), 
                'key' => ltrim($match[1], '!'),
                'val' => [trim($match[2], "\n\r\t\v\x00\"'")],
            ];
        }
        // get mult key values
        $matches = $this->matchKeyMultipleValue($query);
        foreach ($matches as $match) {
            $res[] = [
                'neg' => str_starts_with($match[1], '!'), 
                'key' => ltrim($match[1], '!'),
                // max 16 segments
                'val' => array_map(fn($val) => trim($val, "\n\r\t\v\x00\"'"), explode('|', $match[2], 16)),
            ];
        }
        return $res;
    }

    /**
     * Matches strings of type 
     * `[!]<key>:'<val>'|"<val>"|<val>`
     * @param string $query
     * @return array Array with entries for each key, value pair.
     */
    private function matchKeySingleValue(string $query): array
    {
        $matches = [];
        // key -> $matches[1], value -> $matches[2] 
        preg_match_all(static::$regex_single_match, $query, $matches, PREG_SET_ORDER);
                
        return $matches;
    }

    /**
     * Matches strings of type 
     * `[!]<key>:[<val>|'<val>'|"<val>"|...|<val>]`
     * @param string $query
     * @return array Array with entries for each key, value pair.
     */
    private function matchKeyMultipleValue(string $query): array
    {
        $matches = [];
        preg_match_all(static::$regex_multiple_match, $query, $matches, PREG_SET_ORDER);        
        return $matches;
    }

    //
    // ========= UTIL METHODS =========
    //

    /**
     * Helper function to determine a boolean value of a string
     * @param string $str
     * @return bool|null The value or null of not machted
     */
    protected function to_bool(string $str): ?bool
    {
        return match (strtolower($str)) {
            'f', 'false', 'falsch' => false,
            't', 'true', 'wahr' => true,
            default => null,
        };
    } 

    /**
     * Get error messages
     * @return array{type: string, message: string}
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add error to error set.
     * @param string $type
     * @param string $message
     * @param array $params
     * @return static
     */
    protected function addError(string $type, string $message, array $params = []): static
    {
        $this->errors[] = ['type' => $type, 'message' => $this->translator->trans($message, $params)];
        return $this;
    }
}