<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\AssetHistory;
use App\Entity\CaseFile;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;

/**
 * Class for constructing complex search queries for cases.
 *
 * @author Ben Brooksnieder
 */
class ExtendedCaseSearch extends ExtendedSearch
{
    private bool $assetJoin = false;
    private bool $assetHistoryJoin = false;

    protected function simpleSearchQuery(string $query): QueryBuilder
    {
        /**
         * @var \App\Repository\CaseRepository
         */
        $repo = $this->entityManager->getRepository(CaseFile::class);
        $builder = $repo->createQueryBuilder('caseFile')
            ->where($repo->simpleSearchQuery('caseFile'))
            ->setParameter('search', "%{$query}%");

        return $builder;
    }

    protected function matchQueryKey(string $key, array $data): Andx|Comparison|Func|Orx|string|null
    {
        return match (strtolower($key)) {
            'id', 'c', 'f', 'case', 'fall', 'caseid', 'fallid'                        => $this->caseIdQuery($data['neg'], $data['val']),
            'desc', 'description', 'beschreibung'                                     => $this->descriptionQuery($data['neg'], $data['val']),
            'a', 'active', 'aktiv', 'caseactive', 'fall_aktiv'                        => $this->activeQuery($data['neg'], $data['val']),
            'o', 'b', 'd', 'open', 'begin', 'casebegin', 'offen', 'openedOn', 'datte' => $this->openedOnQuery($data['neg'], $data['val']),
            default => $this->addError('danger', 'ecs.error.tag.unknown', ['tag' => $key]) && false,
        };
    }

    protected function getQueryBuilderWithTables(): QueryBuilder
    {
        $repository = $this->entityManager->getRepository(CaseFile::class);
        $builder = $repository->createQueryBuilder('caseFile');

        // join requiered tables
        if ($this->assetJoin) {
            $builder->leftjoin(Asset::class, 'asset', 'ON', 'asset.case = caseFile.id');
        }
        if ($this->assetHistoryJoin) {
            $builder->leftjoin(AssetHistory::class, 'h_asset', 'ON', 'h_asset.case = caseFile.id');
        }

        return $builder;
    }

    //
    // ========= QUERY METHODS =========
    //

    protected function caseIdQuery(bool $neg, array $values)
    {
        return $this->stringQuery('caseFile.caseId', $neg, $values);
    }

    protected function descriptionQuery(bool $neg, array $values)
    {
        return $this->stringQuery('caseFile.description', $neg, $values);
    }

    protected function activeQuery(bool $neg, array $values)
    {
        if (1 == \count($values) && ($bool = $this->to_bool($values[0])) !== null) {
            return $this->equalQuery('caseFile.active', $neg, [$bool]);
        }

        $this->addError('warning', 'ecs.error.active.invalid');

        return null;
    }

    protected function openedOnQuery(bool $neg, array $values)
    {
        return $this->dateQuery('caseFile.openedOn', $neg, $values);
    }
}
