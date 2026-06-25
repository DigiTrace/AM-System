<?php

namespace App\Service;
use App\Entity\Asset;
use App\Entity\AssetHistory;
use App\Entity\CaseFile;
use App\Entity\Drive;
use App\Entity\Nutzer;
use App\Enum\AssetCategory;
use App\Enum\AssetState;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;

/**
 * Service for parsing complex asset search queries.
 * 
 * @author Ben Brooksnieder
 */
class ExtendedAssetSearch extends ExtendedSearch
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

    /**
     * Return simple text based search on selected columns.
     * @param string $query
     * @return QueryBuilder
     */
    protected function simpleSearchQuery(string $query): QueryBuilder
    {
        /**
         * @var \App\Repository\AssetRepository
         */
        $repo = $this->entityManager->getRepository(Asset::class);
        $builder = $repo->createQueryBuilder('asset')
            ->leftjoin(AssetHistory::class, "h_asset", "ON", "h_asset.asset = asset.barcode")
            ->leftjoin(Drive::class, "drive", "ON", "drive.asset = asset.barcode")
            ->where(
            <<<DQL
            (
                asset.name            LIKE :search 
                OR asset.usage        LIKE :search 
                OR asset.note         LIKE :search 
                OR asset.barcode      LIKE :search 
                OR drive.serialNumber LIKE :search 
            )
            DQL)
            ->setParameter(':search',  "%{$query}%")
        ;

        return $builder;
    }
    
    protected function matchQueryKey(string $key, array $data): Andx|Comparison|Func|Orx|string|null
    {
        return match (strtolower($key)) {
            'c','k','cat','kat','category','kategorie'                      => $this->categoryQuery($data['neg'], $data['val']),
            's','state', 'status'                                           => $this->statusQuery($data['neg'], $data['val']),
            'b','barcode'                                                   => $this->barcodeQuery($data['neg'], $data['val']), 
            'n','name'                                                      => $this->nameQuery($data['neg'], $data['val']),
            'info','note','notiz'                                           => $this->noteQuery($data['neg'], $data['val']),
            'musage', 'usage', 'mdesc','desc','description','beschreibung'  => $this->usageQuery($data['neg'], $data['val']),
            'husage', 'history_usage', 'hdesc','history_description'        => $this->historyUsageQuery($data['neg'], $data['val']),
            'u','mu','user'                                                 => $this->modifiedByQuery($data['neg'], $data['val']),
            'hu', 'history_user'                                            => $this->historyModifiedByQuery($data['neg'], $data['val']),
            'r','mr','reserved','reserviert'                                => $this->reservedQuery($data['neg'], $data['val']),
            'hr','history_reserved'                                         => $this->historyReservedQuery($data['neg'], $data['val']),
            'l','mstoredin','storage','location','container'                => $this->locationQuery($data['neg'], $data['val']),
            'hl','hstoredin','histroy_location'                             => $this->historyLocationQuery($data['neg'], $data['val']),
            'f' , 'mcase' ,'case',  'fall'                                  => $this->caseQuery($data['neg'], $data['val']),
            'hc', 'hcase' ,'history_case'                                   => $this->historyCaseQuery($data['neg'], $data['val']),
            'caseactive', 'fall_aktiv'                                      => $this->caseActiveQuery($data['neg'], $data['val']),
            'type' , 'bauart'                                               => $this->typeQuery($data['neg'], $data['val']),
            'ff' , 'form_factor' , 'form_faktor'                            => $this->formFactorQuery($data['neg'], $data['val']),
            'size' , 'groesse'                                              => $this->sizeQuery($data['neg'], $data['val']),
            'prod','manufacturer','hersteller'                              => $this->manufacturerQuery($data['neg'], $data['val']),
            'modell' , 'model'                                              => $this->modelQuery($data['neg'], $data['val']),
            'pn' , 'product_number','produkt_nummer'                        => $this->productNumberQuery($data['neg'], $data['val']),
            'sn' , 'serial_number','serien_nummer'                          => $this->serialNumberQuery($data['neg'], $data['val']),
            'connection','connector','anschluss'                            => $this->connectorQuery($data['neg'], $data['val']),
            'd', 'ed', 'mdate', 'date'                                      => $this->lastUpdatedOnQuery($data['neg'], $data['val']),
            default => $this->addError('danger', 'eas.error.tag.unknown', ['tag' => $key]) && false,
        };
    }

    protected function getQueryBuilderWithTables(): QueryBuilder 
    {
        $repository = $this->entityManager->getRepository(Asset::class);
        $builder = $repository->createQueryBuilder('asset');

        // join requiered tables
        if ($this->historyJoin)
            $builder->leftjoin(AssetHistory::class, "h_asset", "ON", "h_asset.asset = asset.barcode");
        if ($this->driveJoin)
            $builder->leftjoin(Drive::class, "drive", "ON", "drive.asset = asset.barcode");
        if ($this->userJoin)
            $builder->leftjoin(Nutzer::class, "user", "ON", "user.id = asset.modifiedBy");
        if ($this->historyUserJoin)
            $builder->leftjoin(Nutzer::class, "h_user", "ON", "h_user.id = h_asset.modifiedBy");
        if ($this->reservedUserJoin)
            $builder->leftjoin(Nutzer::class, "reserver", "ON", "reserver.id = asset.reservedBy");
        if ($this->historyReservedUserJoin)
            $builder->leftjoin(Nutzer::class, "h_reserver", "ON", "h_reserver.id = h_asset.reservedBy");
        if ($this->locationJoin)
            $builder->leftjoin(Asset::class, "location", "ON", "location.barcode = asset.location");
        if ($this->historyLocationJoin)
            $builder->leftjoin(Asset::class, "h_location", "ON", "h_location.barcode = h_asset.location");
        if ($this->caseJoin) //  "case" is SQL keyword -> we use "_case"
            $builder->leftjoin(CaseFile::class, "_case", "ON", "_case.id = asset.case");
        if ($this->historyCaseJoin)
            $builder->leftjoin(CaseFile::class, "h_case", "ON", "h_case.id = h_asset.case");

        return $builder;
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
    protected function categoryQuery(bool $neg, array $values): Comparison|Func|string|null
    {
        // translate all categories into categorie ids
        foreach ($values as $key => $c) {
            if(is_numeric($c) && ($c < 0 || $c >= \count(AssetCategory::cases()))) {
                $this->addError('danger', 'eas.error.category.invalid', ['category' => $c]);
                return null;
            }
            else if(!is_numeric($c)){
                $this->addError('info', 'not_implemented_yet', ['function' => 'Named category search' ]);
                return null;
            }
        }
                
        return $this->equalQuery('asset.category', $neg, $values);
    }

    /**
     * Status matching.
     * @todo # TODO matching with translated name of status to id
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Func|string
     */
    protected function statusQuery(bool $neg, array $values): Comparison|Func|string
    {
        // translate all status into status ids
        foreach ($values as $key => $s) {
            if(is_numeric($s) && ($s < 0 || $s >= \count(AssetState::cases()))) {
                $this->addError('danger', 'eas.error.invalid.status %status%', ['%status%' => $s]);
                return null;
            }
            else if(!is_numeric($s)){             
                $this->addError('info', 'not_implemented_yet');
                return null;
            }
        }
                
        return $this->equalQuery('asset.state', $neg, $values);
    }
    
    /**
     * Barcode matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function barcodeQuery(bool $neg, array $values): Comparison|Orx|string
    {
        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('asset.barcode', $neg xor $bool);
        }

        return $this->stringQuery('asset.barcode', $neg, $values);
    }

    /**
     * Name matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function nameQuery(bool $neg, array $values): Comparison|Orx|string
    {
        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
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
    protected function noteQuery(bool $neg, array $values): Comparison|Orx|string
    {
        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('asset.note', $neg xor $bool);
        }

        return $this->stringQuery('asset.note', $neg, $values);
    }

    /**
     * Usage matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function usageQuery(bool $neg, array $values): Comparison|Orx|string
    {
        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('asset.usage', $neg xor $bool);
        }

        return $this->stringQuery('asset.usage', $neg, $values);
    }

    /**
     * Historic usage matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function historyUsageQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->historyJoin = true;
        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('h_asset.usage', $neg xor $bool);
        }

        return $this->stringQuery('h_asset.usage', $neg, $values);
    }

    /**
     * User name matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx
     */
    protected function modifiedByQuery(bool $neg, array $values): Comparison|Orx
    {
        $this->userJoin = true;
        
        return $this->stringQuery('user.fullname', $neg, $values);
    }

    /**
     * History user name matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx
     */
    protected function historyModifiedByQuery(bool $neg, array $values): Comparison|Orx
    {
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
    protected function reservedQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->reservedUserJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
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
    protected function historyReservedQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->historyJoin = true;
        $this->historyReservedUserJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
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
    protected function locationQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->locationJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('location.barcode', $neg xor $bool);
        }

        return $this->stringQuery('location.barcode', $neg, $values);
    }

    /**
     * History location matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function historyLocationQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->historyJoin = true;
        $this->historyLocationJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('h_location.barcode', $neg xor $bool);
        }

        return $this->stringQuery('h_location.barcode', $neg, $values);
    }

    /**
     * Case matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function caseQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->caseJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('_case.caseId', $neg xor $bool);
        }

        return $this->stringQuery('_case.caseId', $neg, $values);
    }

    /**
     * History case matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function historyCaseQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->historyJoin = true;
        $this->historyCaseJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('h_case.caseId', $neg xor $bool);
        }

        return $this->stringQuery('h_case.caseId', $neg, $values);
    }

    protected function caseActiveQuery(bool $neg, array $values): Andx|Comparison|Func|Orx|string|null 
    {
        $this->caseJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->equalQuery('_case.active', $neg, [$bool]);
        }

        $this->addError('warning', 'eas.error.caseactive.invalid');
        return null;
    }

    /**
     * Drive type matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function typeQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->driveJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('drive.type', $neg xor $bool);
        }

        return $this->stringQuery('drive.type', $neg, $values);
    }

    /**
     * Drive form factor matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function formFactorQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->driveJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('drive.formFactor', $neg xor $bool);
        }

        return $this->stringQuery('drive.formFactor', $neg, $values);
    }

    /**
     * Drive size matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function sizeQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->driveJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('drive.size', $neg xor $bool);
        }

        $expr = [];

        foreach ($values as $value) {
            $op = '';
            $val = $value;

            // filter operator
            if(str_starts_with($value, '<=')){
                $op = 'lte';
                $val = substr($value, 2);
            }
            else if (str_starts_with($value, '>=')){
                $op = 'gte';
                $val = substr($value, 2);
            }
            else if (str_starts_with($value, '<')){
                $op = 'lt';
                $val = substr($value, 1);
            }
            else if (str_starts_with($value, '>')){
                $op = 'gt';
                $val = substr($value, 1);
            }

            if(is_numeric($val)){
                if($op){
                    $expr[] = $this->exprBuilder->$op('drive.size', $this->addParam($val));
                }
                else {
                    $expr[] = $this->equalQuery('drive.size', false, [$val]);
                }
            }
            else {
                $expr[] = $this->stringQuery('drive.size', false, [$val]);
            }
        }

        // merge expressions
        if (\count($expr) > 1){
            $expr = $this->exprBuilder->orX(array_shift($expr), ...$expr);
        }
        else {
            $expr = $expr[0];
        }

        return $neg ? $this->exprBuilder->not($expr) : $expr;
    }

    /**
     * Drive manufacturer matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function manufacturerQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->driveJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('drive.manufacturer', $neg xor $bool);
        }

        return $this->stringQuery('drive.manufacturer', $neg, $values);
    }

    /**
     * Drive model matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function modelQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->driveJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('drive.model', $neg xor $bool);
        }

        return $this->stringQuery('drive.model', $neg, $values);
    }

    /**
     * Drive product number matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function productNumberQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->driveJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('drive.productNumber', $neg xor $bool);
        }

        return $this->stringQuery('drive.productNumber', $neg, $values);
    }

    /**
     * Drive serial number matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function serialNumberQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->driveJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('drive.serialNumber', $neg xor $bool);
        }

        return $this->stringQuery('drive.serialNumber', $neg, $values);
    }

    /**
     * Drive connector matching.
     * @param bool $neg Whether to negate query.
     * @param array $values Matching values.
     * @return Comparison|Orx|string
     */
    protected function connectorQuery(bool $neg, array $values): Comparison|Orx|string
    {
        $this->driveJoin = true;

        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null){
            return $this->existenceQuery('drive.connector', $neg xor $bool);
        }

        return $this->stringQuery('drive.connector', $neg, $values);
    }

    protected function lastUpdatedOnQuery(bool $neg, array $values) 
    {
        return $this->dateQuery('asset.lastUpdatedOn', $neg, $values);
    }
}
