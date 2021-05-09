<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\NewsCategory;
use App\Entity\News;
use App\Entity\User;
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
        /** @var News[] $addedNews */
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
                    if ($today > $pubDate || array_key_exists((string)$item->guid, $addedNews)) {
                        continue;
                    }

                    //Добавляем только если еще нет в БД
                    $findNews = $em->getRepository(News::class)->findOneBy(['guid' => (string)$item->guid]);
                    if ($findNews === null) {
                        $news = new News($item, $category);
                        $em->persist($news);

                        $addedNews[(string)$item->guid] = $news;
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

        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * @Route("/", name="news_show", methods={"GET"})
     */
    public function showUserNews(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $categories = $user->getNewsCategories();
        $enterCategory = $request->query->get('category');

        //Если пользователь еще не выбрал категории, то отправляем его на страницу выбора категорий
        if ($categories->count() === 0) {
            return $this->redirectToRoute('enter_category', [
                'id' => $user->getId(),
            ]);
        }

        $userNews = $entityManager->getRepository(News::class)->findBy(
            ['category' => !empty($enterCategory) ? $user->getCategoryByName($enterCategory) : $categories->toArray()],
            ['pubDate' => 'DESC'],
            self::DEFAULT_LIMIT
        );

        return $this->render('news/index.html.twig', [
            'items' => $userNews ?? [],
        ]);
    }

    /**
     * @Route("/original/{guid}", name="news_original", methods={"GET"})
     */
    public function showOriginal(string $guid, EntityManagerInterface $entityManager): Response
    {
        /** @var News $news */
        $news = $entityManager->getRepository(News::class)->find(urldecode($guid));

        return $this->redirect($news->getOriginalLink());
    }
}