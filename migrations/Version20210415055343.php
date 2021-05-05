<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210415055343 extends AbstractMigration
{
    private const FILE_PATH = __DIR__ . '/../categories.json';

    public function getDescription() : string
    {
        return 'Заполнение таблицы с категориями';
    }

    public function up(Schema $schema) : void
    {
        $fileData = \file_get_contents(self::FILE_PATH);
        if ($fileData === false) {
            throw new \RuntimeException('Яндекс.Новости недоступны');
        }

        $categories = json_decode($fileData, true, 512, JSON_THROW_ON_ERROR);

        foreach ($categories as $englishName => $name) {
            $sql = <<<SQL
                INSERT INTO news_category (id, name, english_name)
                VALUES (nextval('news_category_id_seq') ,:categoryName, :englishName)
                SQL;

            $this->addSql($sql, ['categoryName' => $name, 'englishName' => $englishName]);
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
