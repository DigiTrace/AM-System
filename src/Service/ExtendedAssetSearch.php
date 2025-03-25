<?php

namespace App\Service;
use App\Entity\Objekt;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Orx;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * @author Ben Brooksnieder
 */
class ExtendedAssetSearch
{
    private bool $driveJoin = false;
    private bool $userJoin = false;
    private bool $reservedUserJoin = false;
    private bool $locationJoin = false;
    private bool $caseJoin = false;
    
    private bool $historyJoin = false;
    private bool $historyUserJoin = false;
    private bool $historyReservedUserJoin = false;
    private bool $historyLocationJoin = false;
    private bool $historyCaseJoin = false;

    private array $params;
    private array $errors; 
    private Expr $exprBuilder;

    private array $categoryNames;
    private array $categoryNamesTranslated;

    private EntityManagerInterface $entityManagerInterface;
    private TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManagerInterface, TranslatorInterface $translator)
    {
        $this->entityManagerInterface = $entityManagerInterface;
        $this->translator = $translator;
        // get category names
        $this->categoryNames = array_keys(Objekt::$kategorienToId);
        // translate category names to local lang
        $this->categoryNamesTranslated = array_map(fn($c) => $translator->trans($c), $this->categoryNames);
    }

    public function isExtendedQuery(string $query): bool {
        # TODO s
        return !empty($query);
    }

    public function parseQuery(string $query, array &$errors = []): Query|null {
        $this->params = [];
        $this->errors = &$errors;

        // instance of expression builder
        $this->exprBuilder = $this->entityManagerInterface->getExpressionBuilder();
        
        // split query in segments
        $orSegments = explode('||', $query);
        // expression for segments 
        $segmentExprs = [];

        foreach ($orSegments as $segment) {
            // parse segment for key value pairs
            $subquery = $this->getQueryValues($segment);
            if(count($subquery) == 0){
                $this->errors[] = "empty subquery: $segment";
                continue;
            }
            $subexprs = [];
            
            foreach ($subquery as $q) {
                // match each expressions in sub query to sql query string
                $parsedExpr = $this->matchQueryKey($q['key'], $q);
                if ($parsedExpr) {
                    $subexprs[] = $parsedExpr;
                }
            }

            // skip if no valid sub expressions were found
            if(count($subexprs) == 0) {
                $this->errors[] = 'no subqueries';
                continue;
            }

            // if only one sub expression, return that one, else join with AND
            $segmentExprs[] = (count($subexprs) == 1)
                ? $subexprs[0]
                : $this->exprBuilder->andX(array_shift($subexprs), ...$subexprs);
        }

        // return if no valid queries
        if(count($segmentExprs) == 0){
            $this->errors[] = 'empty query';
            return null;
        }

        // if only one segment, return that segment, else join with OR
        $exprs = (count($segmentExprs) == 1) 
            ? $segmentExprs[0]
            : $this->exprBuilder->orX(array_shift($segmentExprs), ...$segmentExprs);

        // get repository    
        $repository = $this->entityManagerInterface->getRepository(Objekt::class);
        $builder = $repository->createQueryBuilder('asset');

        // join requiered tables
        if ($this->historyJoin)
            $builder->leftjoin("App:HistorieObjekt", "h_asset", "WITH", "h_asset.barcode_id = asset.barcode_id");
        if ($this->driveJoin)
            $builder->leftjoin("App:Datentraeger", "drive", "WITH", "drive.barcode_id = asset.barcode_id");
        if ($this->userJoin)
            $builder->leftjoin("App:Nutzer", "user", "WITH", "user.id = asset.nutzer_id");
        if ($this->historyUserJoin)
            $builder->leftjoin("App:Nutzer", "h_user", "WITH", "h_user.id = h_asset.nutzer_id");
        if ($this->reservedUserJoin)
            $builder->leftjoin("App:Nutzer", "reserver", "WITH", "reserver.id = asset.reserviert_von");
        if ($this->historyReservedUserJoin)
            $builder->leftjoin("App:Nutzer", "h_reserver", "WITH", "h_reserver.id = h_asset.reserviert_von");
        if ($this->locationJoin)
            $builder->leftjoin("App:Objekt", "location", "WITH", "location.barcode_id = asset.standort");
        if ($this->historyLocationJoin)
            $builder->leftjoin("App:Objekt", "h_location", "WITH", "h_location.barcode_id = h_asset.standort");
        if ($this->caseJoin)
            $builder->leftjoin("App:Fall", "case", "WITH", "case.id = asset.fall_id");
        if ($this->historyCaseJoin)
            $builder->leftjoin("App:Fall", "h_case", "WITH", "h_case.id = h_asset.fall_id ");

        // place query
        $builder->where($exprs);
        
        // place parameters
        $builder->setParameters($this->params);

        return $builder->getQuery();
    }

    /**
     * Matches $key to any known search query keyword and returns fitting subquery.
     * @param string $key                                     Search keyword
     * @param array{key: string, neg: bool, val: array} $data Argument data
     * @return Andx|Comparison|Func|Orx|string|null
     */
    protected function matchQueryKey(string $key, $data): Andx|Comparison|Func|Orx|string|null {
        return match (strtolower($key)) {
            'c','k','cat','kat','category','kategorie'      => $this->categoryQuery($data['neg'], $data['val']),
            's','status'                                    => $this->statusQuery($data['neg'], $data['val']),
            'b','barcode'                                   => $this->barcodeQuery($data['neg'], $data['val']), 
            'n','name'                                      => $this->nameQuery($data['neg'], $data['val']),
            'notice','note','notiz'                         => $this->noteQuery($data['neg'], $data['val']),
            'mdesc','desc','description','beschreibung'     => $this->descriptionQuery($data['neg'], $data['val']),
            'hdesc','history_description'                   => $this->historyDescriptionQuery($data['neg'], $data['val']),
            'u','mu','user'                                 => $this->userChangeQuery($data['neg'], $data['val']),
            'hu',                                           => $this->historyUserChangeQuery($data['neg'], $data['val']),
            'r','mr','reserved','reserviert'                => $this->reservedQuery($data['neg'], $data['val']),
            'hr','history_reserved'                         => $this->historyReservedQuery($data['neg'], $data['val']),
            'l','mstoredin','storage','location','container'=> $this->locationQuery($data['neg'], $data['val']),
            'hl','hstoredin','histroy_location'             => $this->historyLocationQuery($data['neg'], $data['val']),
            'f' , 'mcase' ,'case',  'fall'                  => $this->caseQuery($data['neg'], $data['val']),
            'hc', 'hcase' ,'history_case'                   => $this->historyCaseQuery($data['neg'], $data['val']),
            'caseactive', 'fall_aktiv'                      => $this->caseActiveQuery($data['neg'], $data['val']),
            'type' , 'bauart'                               => $this->typeQuery($data['neg'], $data['val']),
            'ff' , 'form_factor' , 'form_faktor'            => $this->formFactorQuery($data['neg'], $data['val']),
            'size' , 'groesse'                              => $this->sizeQuery($data['neg'], $data['val']),
            'prod','manufacturer','hersteller'              => $this->manufacturerQuery($data['neg'], $data['val']),
            'modell' , 'model'                              => $this->modelQuery($data['neg'], $data['val']),
            'pn' , 'product_number','produkt_nummer'        => $this->productNumberQuery($data['neg'], $data['val']),
            'sn' , 'serial_number','serien_nummer'          => $this->serialNumberQuery($data['neg'], $data['val']),
            'connection','connector','anschluss'            => $this->connectorQuery($data['neg'], $data['val']),
            'd', 'mdate'                                    => $this->dateQuery($data['neg'], $data['val']),
            default => "unknown: $key",
        };
    }

    //
    // ========= QUERY METHODS =========
    //

    /**
     * Category matching.
     * @todo #TODO matching with translated name of category to id
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Func|string|null
     */
    protected function categoryQuery(bool $neg, array $values): Comparison|Func|string|null {
        // translate all categories into categorie ids
        foreach ($values as $key => $c) {
            if(is_numeric($c) && ($c < 0 || $c >= Objekt::getCountCategories())) {
                $this->errors[] = 'category.number.is.wrong';
                return null;
            }
            else if(!is_numeric($c)){
                // $categories[$key] = Objekt::$kategorienToId["category.{$category}"];
                $this->errors[] = 'missing implementation';
                return null;
            }
        }
                
        return $this->equalQuery('asset.kategorie_id', $neg, $values);
    }

    /**
     * Status matching.
     * @todo # TODO matching with translated name of status to id
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Func|string
     */
    protected function statusQuery(bool $neg, array $values): Comparison|Func|string {
        // translate all status into status ids
        foreach ($values as $key => $s) {
            if(is_numeric($s) && ($s < 0 || $s >= Objekt::getCountStatues())) {
                $this->errors[] = 'status.number.is.wrong';
                return null;
            }
            else if(!is_numeric($s)){             
                $this->errors[] = 'missing implementation';
                return null;
            }
        }
                
        return $this->equalQuery('asset.status_id', $neg, $values);
    }
    
    /**
     * Barcode matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function barcodeQuery(bool $neg, array $values): Comparison|Orx|string { 
        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('asset.barcode_id', $neg xor $bool);
        }

        return $this->stringQuery('asset.barcode_id', $neg, $values);
    }

    /**
     * Name matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function nameQuery(bool $neg, array $values): Comparison|Orx|string {
        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('asset.name', $neg xor $bool);
        }

        return $this->stringQuery('asset.name', $neg, $values);
    }

    /**
     * Note matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function noteQuery(bool $neg, array $values): Comparison|Orx|string {
        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('asset.notiz', $neg xor $bool);
        }

        return $this->stringQuery('asset.notiz', $neg, $values);
    }

    /**
     * Description matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function descriptionQuery(bool $neg, array $values): Comparison|Orx|string {
        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('asset.verwendung', $neg xor $bool);
        }

        return $this->stringQuery('asset.verwendung', $neg, $values);
    }

    /**
     * Historic description matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function historyDescriptionQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->historyJoin = true;
        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('h_asset.verwendung', $neg xor $bool);
        }

        return $this->stringQuery('h_asset.verwendung', $neg, $values);
    }

    /**
     * User name matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx
     */
    protected function userChangeQuery(bool $neg, array $values): Comparison|Orx {
        $this->userJoin = true;
        return $this->stringQuery('user.fullname', $neg, $values);
    }

    /**
     * History user name matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx
     */
    protected function historyUserChangeQuery(bool $neg, array $values): Comparison|Orx {
        $this->historyJoin = true;
        $this->historyUserJoin = true;
        return $this->stringQuery('h_user.fullname', $neg, $values);
    }

    /**
     * Reserved matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function reservedQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->reservedUserJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('reserver.fullname', $neg xor $bool);
        }

        return $this->stringQuery('reserver.fullname', $neg, $values);
    }

    /**
     * History reserved matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function historyReservedQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->historyJoin = true;
        $this->historyReservedUserJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('h_reserver.fullname', $neg xor $bool);
        }

        return $this->stringQuery('h_reserver.fullname', $neg, $values);
    }

    /**
     * Location matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function locationQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->locationJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('location.barcode_id', $neg xor $bool);
        }

        return $this->stringQuery('location.barcode_id', $neg, $values);
    }

    /**
     * History location matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function historyLocationQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->historyJoin = true;
        $this->historyLocationJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('h_location.barcode_id', $neg xor $bool);
        }

        return $this->stringQuery('h_location.barcode_id', $neg, $values);
    }

    /**
     * Case matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function caseQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->caseJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('case.case_id', $neg xor $bool);
        }

        return $this->stringQuery('case.case_id', $neg, $values);
    }

    /**
     * History case matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function historyCaseQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->historyJoin = true;
        $this->historyCaseJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('h_case.case_id', $neg xor $bool);
        }

        return $this->stringQuery('h_case.case_id', $neg, $values);
    }

    protected function caseActiveQuery(bool $neg, array $values): Andx|Comparison|Func|Orx|string {
        if(is_array($values)){
            return 'assetsearch.mult.invalid';
        }

        $this->caseJoin = true;

        if($neg)    
            return $this->exprBuilder->eq('case.ist_aktiv', $this->addParam($values[0]));
        else
            return $this->exprBuilder->isNull('case.ist_aktiv');
    }

    /**
     * Drive type matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function typeQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->driveJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('drive.bauart', $neg xor $bool);
        }

        return $this->stringQuery('drive.bauart', $neg, $values);
    }

    /**
     * Drive form factor matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function formFactorQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->driveJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('drive.formfaktor', $neg xor $bool);
        }

        return $this->stringQuery('drive.formfaktor', $neg, $values);
    }

    /**
     * Drive size matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function sizeQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->driveJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('drive.size', $neg xor $bool);
        }

        return $this->stringQuery('drive.size', $neg, $values);
    }

    /**
     * Drive manufacturer matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function manufacturerQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->driveJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('drive.hersteller', $neg xor $bool);
        }

        return $this->stringQuery('drive.hersteller', $neg, $values);
    }

    /**
     * Drive model matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function modelQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->driveJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('drive.modell', $neg xor $bool);
        }

        return $this->stringQuery('drive.modell', $neg, $values);
    }

    /**
     * Drive product number matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function productNumberQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->driveJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('drive.pn', $neg xor $bool);
        }

        return $this->stringQuery('drive.pn', $neg, $values);
    }

    /**
     * Drive serial number matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function serialNumberQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->driveJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('drive.sn', $neg xor $bool);
        }

        return $this->stringQuery('drive.sn', $neg, $values);
    }

    /**
     * Drive connector matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function connectorQuery(bool $neg, array $values): Comparison|Orx|string {
        $this->driveJoin = true;

        if (1 == count($values) && $bool = $this->to_bool($values[0])){
            return $this->existenceQuery('drive.anschluss', $neg xor $bool);
        }

        return $this->stringQuery('drive.anschluss', $neg, $values);
    }
    

    /**
     * Date query, supports ranges with > and < modifier.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Func|Orx
     */
    protected function dateQuery(bool $neg, array $values): Comparison|Func|Orx {
        $pattern = '/(?<operator>[<>]|<=|>=)?(?<day>\d{2})[-\.](?<month>\d{2})[-\.](?<year>\d{4}|\d{2})/';
        
        // try to parse dates
        $values = array_filter($values, function($val) use ($pattern){
            $matches = [];
            if(!preg_match($pattern, $val, $matches)){   
                $this->errors[] = 'Invalid date';
                return false;
            }

            return (bool) date_create(str_pad($matches['year'], 4, '20', STR_PAD_LEFT)."-{$matches['month']}-{$matches['day']}");
        });

        // parse date queries
        $exprs = array_map(function($date) use ($pattern){
            $matches = [];
            preg_match($pattern, $date, $matches);
            $param = $this->addParam(str_pad($matches['year'], 4, '20', STR_PAD_LEFT)."-{$matches['month']}-{$matches['day']}");
            return match ($matches['operator']) {
                '' => $this->exprBuilder->eq("DATE_DIFF(asset.zeitstempel, $param)", 0),
                '<' => $this->exprBuilder->lt("DATE_DIFF(asset.zeitstempel, $param)", 0),
                '<=' => $this->exprBuilder->lte("DATE_DIFF(asset.zeitstempel, $param)", 0),
                '>' => $this->exprBuilder->gt("DATE_DIFF(asset.zeitstempel, $param)", 0),
                '>=' => $this->exprBuilder->gte("DATE_DIFF(asset.zeitstempel, $param)", 0),
            };
        }, $values);

        // join expressions with OR
        $expr = (count($exprs) == 1) 
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
    
    //
    // ========= UTIL METHODS =========
    //

    /**
     * Helper function to determine a boolean value of a string
     * @param string $str
     * @return bool|null The value or null of not machted
     */
    private function to_bool(string $str): bool|null{
        return match (strtolower($str)) {
            ''|'0'|'f'|'false'|'falsch' => false,
            '1'|'t'|'true'|'wahr' => true,
            default => null,
        };
    } 

    /**
     * Creates SQL expression for string search.
     * @param string $identifier    Identifier of field
     * @param bool $neg             Whether to negate query
     * @param array $values         Search values, needs to match at least one
     * @return Comparison|Orx
     */
    private function stringQuery(string $identifier, bool $neg, array $values): Comparison|Orx {
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
        if (1 == count($values)) {
            return $exprs[0];
        }
        else {            
            return $this->exprBuilder->orX(array_shift($exprs), ...$exprs);
        }
    }

    /**
     * Creates SQL expression for equality of one or more possible values.
     * @param string $identifier    Identifier of field
     * @param bool $neg             Whether to negate query
     * @param array $values         Search values, needs to match at least one
     * @return Comparison|Func
     */
    private function equalQuery(string $identifier, bool $neg, array $values): Comparison|Func {
        if (is_string($values))
            $values = [$values];

        // if only one value present, to simple comparison
        if (count($values) == 1){
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
    private function existenceQuery(string $identifier, bool $exists): string {
        if($exists)    
            return $this->exprBuilder->isNotNull($identifier);
        else
            return $this->exprBuilder->isNull($identifier);
    }

    /**
     * Adds parameter to parameter list and returns parameter binding.
     * @param mixed $parameter Parameter to add
     * @return string Reference in the form of "?x"
     */
    private function addParam($parameter): string {
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
    private function getQueryValues(string $query): array {
        $res = [];
        // get single key values
        $matches = $this->matchKeySingleValue($query);
        foreach ($matches as $match) {
            $res[] = [
                'neg' => str_starts_with($match['key'], '!'), 
                'key' => ltrim($match['key'], '!'),
                'val' => [trim($match['val'], " \n\r\t\v\x00\"'")],
            ];
        }
        // get mult key values
        $matches = $this->matchKeyMultipleValue($query);
        foreach ($matches as $match) {
            $res[] = [
                'neg' => str_starts_with($match['key'], '!'), 
                'key' => ltrim($match['key'], '!'),
                'val' => array_map(fn($val) => trim($val, " \n\r\t\v\x00\"'"), explode('|', $match['val'], 8)),
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
    private function matchKeySingleValue(string $query): array {
        $matches = [];
        preg_match_all('/(?<key>!?\w+)[:=](?<val>(?:(?:(["\'])[\w <>\-\.!üÜöÖäÄ]+)\g-1)|(?:[\w<>\-\.!üÜöÖäÄ]+))/', $query, $matches, PREG_SET_ORDER);
                
        return $matches;
    }

    /**
     * Matches strings of type 
     * `[!]<key>:[<val>|'<val>'|"<val>"|...|<val>]`
     * @param string $query
     * @return array Array with entries for each key, value pair.
     */
    private function matchKeyMultipleValue(string $query): array {
        $matches = [];
        preg_match_all('/(?<key>!?\w+)[:=]\[(?<val>(?:(["\']?)[\w <>\-\.!üÜöÖäÄ]+\3\|)*(["\']?)[\w <>\-\.!üÜöÖäÄ]+\4)\]/', $query, $matches, PREG_SET_ORDER);        
        return $matches;
    }
}
