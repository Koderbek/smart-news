<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\NewsCategory;
use App\Entity\News;
use App\Entity\User;
use App\Services\Elasticsearch\ElasticsearchServiceFactory;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Class NewsController
 * @package App\Controller
 *
 * @Route("/news")
 */
class NewsController extends AbstractController
{
    private const DEFAULT_LIMIT = 40;

    /**
     * @Route("/add", name="news_add", methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     *
     * @return Response
     */
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        /** @var News[] $indexNews */
        $indexNews = [];
        $addedNews = [];
        $today = strtotime(date('d-m-Y'));

        $params = $request->query->get('categories');
        if (empty($params)) {
            $categories = $em->getRepository(NewsCategory::class)->findAll();
        } else {
            $categories = $em->getRepository(NewsCategory::class)->findBy(['englishName' => $params]);
        }

        /** @var NewsCategory $category */
        foreach ($categories as $category) {
            $url = News::RSS_HOST . $category->getEnglishName() . '.rss';
            $content = file_get_contents($url);
            if ($content === false) {
                throw new RuntimeException('Яндекс.Новости недоступны');
            }

            try {
                $items = new SimpleXMLElement($content);
                $items = $items->channel->item;

                $count = 0;
                foreach ($items as $item) {
                    $pubDate = strtotime((string)$item->pubDate);
                    if (
                        $today > $pubDate
                        || array_key_exists((string)$item->guid, $addedNews)
                        || in_array((string)$item->title, $addedNews, true)
                    ) {
                        continue;
                    }

                    //Добавляем только если еще нет в БД
                    $findNews = $em->getRepository(News::class)->findOneBy(['guid' => (string)$item->guid]);
                    if ($findNews === null) {
                        $news = new News($item, $category);
                        $em->persist($news);

                        $indexNews[] = $news;
                        $addedNews[(string)$item->guid] = (string)$item->title;
                        $count++;

                        if ($count === 20) {
                            $count = 0;
                            $em->flush();
                        }
                    }
                }

                $em->flush();
            } catch (Throwable $e) {
                throw new RuntimeException($e->getMessage());
            }
        }

        $elasticsearchService = ElasticsearchServiceFactory::createService();
        $elasticsearchService->indexNews($indexNews);

        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * @Route("/", name="news_show", methods={"GET"})
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function showUserNews(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $categories = $user->getNewsCategories();
        $enterCategory = $request->query->get('category');
        $userNews = [];
        $tapeType = null;

        //Если пользователь еще не выбрал категории, то отправляем его на страницу выбора категорий
        if ($categories->count() === 0) {
            return $this->redirectToRoute('enter_category', [
                'id' => $user->getId(),
            ]);
        }

        $repository = $entityManager->getRepository(News::class);

        //Если фильтра нет, то ищем рекомендации
        if (empty($enterCategory)) {
            $userNews = $repository->findRecommendations($user);
            $tapeType = 'Рекомендации';
        }

        if ($userNews === []) {
            $category = !empty($enterCategory) ? $user->getCategoryByName($enterCategory) : null;

            $userNews = $repository->findBy(
                ['category' => $category ?? $categories->toArray()],
                ['pubDate' => 'DESC'],
                self::DEFAULT_LIMIT
            );

            $tapeType = isset($category) ? $category->getName() : null;
        }

        return $this->render('news/index.html.twig', [
            'items' => $userNews,
            'tapeType' => $tapeType
        ]);
    }

    /**
     * @Route("/original/{id}", name="news_original", methods={"GET"})
     *
     * @param News $news
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function showOriginal(News $news, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->addLikedNews($news);

        $entityManager->flush();

        return $this->redirect($news->getOriginalLink());
    }

    /**
     * @Route("/{id}/{score}", name="news_score", methods={"GET"}, requirements={"id": "\d+"})
     *
     * @param News $news
     * @param string $score
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function scoreNews(News $news, string $score, EntityManagerInterface $entityManager): Response
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if ($score === 'like') {
                $user->addLikedNews($news);
            } else {
                $user->removeLikedNews($news);
            }

            $entityManager->flush();

            return new Response('OK');
        } catch (Throwable $exception) {
            throw new RuntimeException($exception->getMessage());
        }
    }

    /**
     * @Route("/search/{phrase}", name="news_search", methods={"GET"})
     *
     * @param string $phrase
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function searchNews(string $phrase, EntityManagerInterface $entityManager): Response
    {
        $elasticsearchService = ElasticsearchServiceFactory::createService();
        $newsIds = $elasticsearchService->searchNewsByPhrase($phrase);

        $newsRepository = $entityManager->getRepository(News::class);
        $news = [];
        if ($newsIds !== []) {
            //Важен порядок выдачи, поэтому дергаем поочередно
            foreach ($newsIds as $id) {
                $news[] = $newsRepository->find($id);
            }
        }

        return $this->render('news/index.html.twig', [
            'items' => $news,
            'tapeType' => '',
        ]);
    }
}