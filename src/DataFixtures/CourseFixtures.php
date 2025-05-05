<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            ['Cursus d’initiation à la guitare 1', 'lorem ipsum.', 50],
        ];

        foreach ($courses as $index => [$title, $desc, $price]) {
            $course = new Course();
            $course->setTitle($title);
            $course->setDescription($desc);
            $course->setPrice($price);
            $manager->persist($course);

            $this->addReference('course_' . $index, $course);
        }

        $manager->flush();
    }
}
