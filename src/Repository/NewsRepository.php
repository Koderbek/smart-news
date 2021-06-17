<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\News;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewsRepository extends ServiceEntityRepository
{
    /** @var int */
    private const LIMIT = 40;

    /** @var int - процент схожести */
    private const SIMILARITY_PERCENTAGE = 80;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * @param User $user
     * @return array|News[]
     */
    public function findRecommendations(User $user): array
    {
        //Поиск схожих пользователей
        $sql = <<<SQL
            SELECT un.user_id FROM user_news un
            WHERE un.news_id IN (SELECT uns.news_id FROM user_news uns WHERE uns.user_id = :user_id)
              AND un.user_id IN (SELECT unc.user_id FROM user_news_category unc
                                 WHERE unc.news_category_id IN (SELECT uc.news_category_id
                                                                FROM user_news_category uc
                                                                WHERE uc.user_id = :user_id)
                                   AND unc.user_id != :user_id
                                 GROUP BY unc.user_id
                                 HAVING count(unc.news_category_id) >= :category_count)
            GROUP BY un.user_id
            HAVING count(un.news_id) >= :like_count
            ORDER BY count(un.news_id) DESC;
            SQL;

        $params = [
            'user_id' => $user->getId(),
            'category_count' => ($user->getNewsCategories()->count() / 100) * self::SIMILARITY_PERCENTAGE,
            'like_count' => ($user->getLikedNews()->count() / 100) * self::SIMILARITY_PERCENTAGE
        ];


        $conn = $this->getEntityManager()->getConnection();

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAllAssociative();

        $userIds = $this->getUserIds($result);
        $users = $this->getEntityManager()->getRepository(User::class)->findById($userIds);

        return $this->getRecommendationNews($user, $users);
    }

    /**
     * @param array $users
     * @return array
     */
    private function getUserIds(array $users): array
    {
        $ids = [];
        foreach ($users as $user) {
            $ids[] = $user['user_id'];
        }

        return $ids;
    }

    /**
     * @return array|News[]
     */
    private function getRecommendationNews(User $currentUser, array $users): array
    {
        $news = [];
        /** @var User $user */
        foreach ($users as $user) {
            $likedNews = $user->getWeekLikedNews();
            /** @var News $value */
            foreach ($likedNews as $value) {
                //Устанавливаем лимит в 40 записей
                if (count($news) >= self::LIMIT) {
                    break;
                }

                //Если новость уже была добавлена
                //или пользователь уже просматривал ее
                //или новость не из категории пользователя, то пропускаем
                if (
                    array_key_exists($value->getId(), $news)
                    || $currentUser->isLikedNews($value)
                    || empty($currentUser->getCategoryByName($value->getCategory()->getEnglishName()))
                ) {
                    continue;
                }

                $news[$value->getId()] = $value;
            }
        }

        return $news;
    }
}