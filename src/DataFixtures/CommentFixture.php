<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class CommentFixture extends BaseFixture implements DependentFixtureInterface
{
    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(Comment::class, 10, function (Comment $comment) {
            $comment->setAuthorName($this->faker->name())
                ->setContent($this->faker->boolean ? $this->faker->paragraph : $this->faker->sentences(2, true))
                ->setArticle($this->getRandomReference(Article::class))
                ->setCreatedAt($this->faker->dateTimeBetween('-1 months', '-1 seconds'))
                ->setIsDeleted($this->faker->boolean(20))
            ;
        });
        $manager->flush();
    }
    public function getDependencies()
    {
        return [ArticleFixture::class];
    }
}
