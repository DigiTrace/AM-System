<?php

namespace App\Repository;

use App\Entity\Asset;
use App\Entity\AssetHistory;
use App\Entity\Fall;
use App\Enum\AssetCategory;
use App\Enum\AssetState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Ben Brooksnieder
 *
 * @extends ServiceEntityRepository<Asset>
 *
 * @method Asset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Asset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Asset[]    findAll()
 * @method Asset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Asset::class);
    }

    /**
     * Find all objects reserved by `user`.
     *
     * @param int|null $limit optional limit max number of results
     *
     * @return self[]
     */
    public function findAllReservedByUser(int|UserInterface $user, ?int $limit = null): array
    {
        // get user id
        if (!\is_int($user)) {
            $user = $user->getId();
        }

        // create query builder
        $builder = $this->createQueryBuilder('a')
            ->where('a.reservedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('a.lastUpdatedOn', 'DESC');

        if (null !== $limit) {
            $builder->setMaxResults($limit);
        }

        // get and run query
        $query = $builder->getQuery();

        return $query->execute();
    }

    /**
     * Get all assets (objects) that were previously involved with a specific case.
     *
     * @param Fall $case Associated case
     *
     * @return Asset[]
     */
    public function findPreviouslyInvolvedInCase(Fall $case): array
    {
        // TODO optimize query
        $builder = $this->createQueryBuilder('a')
            ->join(AssetHistory::class, 'ha', 'with', 'ha.asset = a.barcode')
            ->leftJoin(Asset::class, 'sa', 'with', 'sa.barcode = a.location')
            ->where('ha.case = :case')
            ->andWhere('a.case != :case OR a.case IS NULL')
            ->setParameter('case', $case->getId());

        $query = $builder->getQuery();

        return $query->getResult();
    }

    /**
     * Get all assets (objects) that function as storage.
     *
     * @return Asset[]
     */
    public function findAllStorageAssets(?string $search = null, ?int $limit = null): array
    {
        $builder = $this->createQueryBuilder('a')
        ->where($this->isStorageQuery('a'))
        ->andWhere($this->isEditableQuery('a'))
        ->orderBy('a.lastUpdatedOn', 'DESC');

        if ($search !== null) {
            $builder->andWhere('a.barcode like :search OR a.name like :search')
            ->setParameter('search', "%{$search}%");
        }

        if ($limit !== null) {
            $builder->setMaxResults($limit);
        }

        // get and run query
        $query = $builder->getQuery();

        // returns an array of Product objects
        return $query->getResult();
    }


    /**
     * Get all assets that are valid hdd image targets.
     *
     * @return Asset[]
     */
    public function findAllHddImageTargetAssets(?Asset $exclude, ?string $search = null, ?int $limit = null): array
    {
        $builder = $this->createQueryBuilder('a')
        ->where($this->isHddImageTargetQuery('a'))
        ->andWhere($this->isEditableQuery('a'))
        ->orderBy('a.lastUpdatedOn', 'DESC');

        if ($exclude !== null) {
            $builder->andHaving(':exclude NOT MEMBER OF a.images')
                    ->andHaving(':exclude NOT MEMBER OF a.hdds')
                    ->setParameter(':exclude', $exclude);
        }

        if ($search !== null) {
            $builder->andWhere('a.barcode like :search OR a.name like :search')
            ->setParameter('search', "%{$search}%");
        }

        if ($limit !== null) {
            $builder->setMaxResults($limit);
        }

        // get and run query
        $query = $builder->getQuery();

        // returns an array of Product objects
        return $query->getResult();
    }


    /**
     * Get all assets that are valid hdd image sources.
     *
     * @return Asset[]
     */
    public function findAllHddImageSourceAssets(?Asset $exclude, ?string $search = null, ?int $limit = null): array
    {
        $builder = $this->createQueryBuilder('a')
        ->where($this->isHddImageSourceQuery('a'))
        ->andWhere($this->isEditableQuery('a'))
        ->orderBy('a.lastUpdatedOn', 'DESC');

        if ($exclude !== null) {
            $builder->andHaving(':exclude NOT MEMBER OF a.images')
                    ->andHaving(':exclude NOT MEMBER OF a.hdds')
                    ->setParameter(':exclude', $exclude);
        }

        if ($search !== null) {
            $builder->andWhere('a.barcode like :search OR a.name like :search')
            ->setParameter('search', "%{$search}%");
        }

        if ($limit !== null) {
            $builder->setMaxResults($limit);
        }

        // get and run query
        $query = $builder->getQuery();

        // returns an array of Product objects
        return $query->getResult();
    }


    /**
     * Builds query that filters for all assets with storage ability.
     *
     * @param string $alias alias used in query for table
     */
    public function isStorageQuery(string $alias)
    {
        $builder = $this->getEntityManager()->getExpressionBuilder();
        $categoryIds = array_map(fn ($c) => $c->value, AssetCategory::getStorageCategories());

        // if `isStorageOverride` === null -> base on Categories
        $query = $builder->isNull("$alias.storageOverride");
        $query = $builder->andX($query, $builder->in("$alias.category", $categoryIds));
        // else use override attribute
        $query = $builder->orX($query, "$alias.storageOverride = 1");

        return "($query)";
    }

    /**
     * Builds query that filters for all assets that DO NOT have storage ability.
     *
     * @param string $alias alias used in query for table
     */
    public function isNotStorageQuery(string $alias)
    {
        $builder = $this->getEntityManager()->getExpressionBuilder();
        $categoryIds = array_map(fn ($c) => $c->value, AssetCategory::getStorageCategories());

        // if `isStorageOverride` === null -> base on Categories
        $query = $builder->isNull("$alias.storageOverride");
        $query = $builder->andX($query, $builder->notIn("$alias.category", $categoryIds));
        // else use override attribute
        $query = $builder->orX($query, "$alias.storageOverride = 0");

        return "($query)";
    }


    /**
     * Builds query that filters for all assets that are targets for hdd images from other sources.
     *
     * @param string $alias alias used in query for table
     */
    public function isHddImageTargetQuery(string $alias)
    {
        $builder = $this->getEntityManager()->getExpressionBuilder();
        $allowedCategories = array_map(fn ($c) => $c->value, AssetCategory::getHddImageTargetCategories());
        
        // check that category is in allowed list
        $query = $builder->in("$alias.category", $allowedCategories);
        return "($query)";
    }


    /**
     * Builds query that filters for all assets that are possible sources for hdd images to be stored on target (hdds).
     *
     * @param string $alias alias used in query for table
     */
    public function isHddImageSourceQuery(string $alias)
    {
        $builder = $this->getEntityManager()->getExpressionBuilder();
        $allowedCategories = array_map(fn ($c) => $c->value, AssetCategory::getHddImageSourceCategories());
        
        // check that category is in allowed list
        $query = $builder->in("$alias.category", $allowedCategories);
        return "($query)";
    }

    /**
     * Builds query that filters for all editable assets.
     *
     * @param string $alias alias used in query for table
     */
    public function isEditableQuery(string $alias) {
        $builder = $this->getEntityManager()->getExpressionBuilder();
        $allowedStates = array_map(fn ($c) => $c->value, AssetState::getEditableStates());

        // check that state is not in list
        $query = $builder->in("$alias.state", $allowedStates);
        return "($query)";
    }

    /**
     * Builds query that filters for all non-editable assets.
     *
     * @param string $alias alias used in query for table
     */
    public function isNotEditableQuery(string $alias) {
        $builder = $this->getEntityManager()->getExpressionBuilder();
        $allowedStates = array_map(fn ($c) => $c->value, AssetState::getEditableStates());

        // check that state is not in list
        $query = $builder->notIn("$alias.state", $allowedStates);
        return "($query)";
    }
}
