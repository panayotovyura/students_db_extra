<?php
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Entity\Student;

// require Composer's autoloader
$loader = require __DIR__.'/vendor/autoload.php';
// auto-load annotations
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->loadFromExtension('framework', array(
            'secret' => 'S0ME_SECRET'
        ));

        $c->loadFromExtension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'charset' => 'UTF8',
                'path' => '%kernel.root_dir%/data/data.db3',
            ],
            'orm' => [
                'entity_managers' => [
                    'default' => [
                        'mappings' => [
                            'custom_mapping' => [
                                'type' => 'annotation',
                                'prefix' => 'Entity',
                                'dir' => "%kernel.root_dir%/src/Entity/",
                                'is_bundle' => false,
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->add('/students/detail/{path}', 'kernel:studentsDetailAction');
    }

    public function studentsDetailAction($path)
    {
        $student = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository(Student::class)
            ->findOneByPath($path);

        if (!$student) {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        return
            (new Response(
                '<h1>' . $student->getName() . '</h1>' .
                '<p>' . $student->getDescription() . '</p>' .
                '<p>' . date('d.m.Y H:m:s') . '</p>'
            ))
            ->setPublic()
            ->setExpires(
                (new \DateTime())->modify('+15 minutes')
            );
    }
}

class AppCache extends HttpCache
{
}

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
$kernel = new AppCache($kernel);
Request::enableHttpMethodParameterOverride();

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);