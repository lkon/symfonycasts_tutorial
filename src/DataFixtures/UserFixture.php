<?php

namespace App\DataFixtures;

use App\Entity\ApiToken;
use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixture extends BaseFixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $userPasswordEncoder;

    /**
     * UserFixture constructor.
     */
    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    protected function loadData(ObjectManager $manager)
    {
        $this->createManyWithReference(10, 'main_users', function ($i) use ($manager){
            $user = new User();
            $user->setEmail(sprintf('spacebar%d@example.com', $i))
                ->setFirstName($this->faker->firstName)
                ->setPassword($this->userPasswordEncoder->encodePassword($user, 'engage'))
                ->setTwitterUsername($this->faker->userName)
                ->agreeTerms()
                ;

            $apiToken1 = new ApiToken($user);
            $manager->persist($apiToken1);
            $apiToken2 = new ApiToken($user);
            $manager->persist($apiToken2);

            return $user;
        });

        $this->createManyWithReference(3, 'admin_users', function ($i) {
            $user = new User();
            $user->setEmail(sprintf('admin%d@thesacebar.com', $i))
                ->setFirstName($this->faker->firstName)
                ->setRoles(['ROLE_ADMIN'])
                ->setPassword($this->userPasswordEncoder->encodePassword($user, 'engage'))
                ->agreeTerms()
            ;

            return $user;
        });

        $manager->flush();
    }
}
