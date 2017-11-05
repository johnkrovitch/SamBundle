<?php

namespace JK\SamBundle\Tests\Command;

use JK\SamBundle\Command\BuildCommand;
use JK\SamBundle\Event\Subscriber\NotificationSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BuildCommandTest extends TestCase
{
    public function testRun()
    {
        $container = $this
            ->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        // mock event dispatcher
        $eventDispatcher = $this
            ->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        // mock assets configuration
        $configuration = [
            'debug' => true,
            'filters' => [
                'compass' => [],
                'merge' => [],
                'minify' => [],
                'copy' => [],
            ],
            'tasks' => [
                'main.css' => [
                    'filters' => [
                        'compass',
                        'merge',
                        'minify',
                        'copy',
                    ],
                    'sources' => [
                        'Tests/Resources/assets/scss/main.scss',
                        'Tests/Resources/assets/scss/custom.scss',
                    ],
                    'destinations' => [
                        'web/css/main.css',
                    ],
                ],
            ],
        ];
        
        // mock notification subscriber
        $notificationSubscriber = $this
            ->getMockBuilder(NotificationSubscriber::class)
            ->getMock()
        ;
        $notificationSubscriber
            ->expects($this->once())
            ->method('getNotifications')
            ->willReturn([
                'Success !!!',
            ])
        ;
        
        $container
            ->method('getParameter')
            ->willReturnMap([
                ['jk.assets', $configuration],
                ['kernel.root_dir', realpath(__DIR__.'/../..')],
            ])
        ;
        $c = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
        $container
            ->method('get')
            ->willReturnMap([
                ['event_dispatcher', $c, $eventDispatcher],
                ['jk.assets.notification_subscriber', $c,  $notificationSubscriber]
            ])
        ;
        
        $command = new BuildCommand();
        $command->setContainer($container);
    
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->run($input, $output);
    
        $content = $output->fetch();
    
        $this->assertContains('Building tasks', $content);
        $this->assertContains('Tasks build', $content);
        $this->assertContains('Building filters', $content);
        $this->assertContains('Filters build', $content);
        $this->assertContains('Running tasks', $content);
        $this->assertContains('Running main.css', $content);
        $this->assertContains('x Success !!!', $content);
        $this->assertContains('[OK] Assets build end', $content);
    
        $fileContent = 'body{color:blue}a{color:red}';
        $this->assertEquals(file_get_contents(__DIR__.'/../../web/css/main.css'), $fileContent);
    }
}
