<?php
namespace Consolidation\AnnotatedCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\AnnotatedCommand\AnnotationData;

class AnnotatedCommandFactoryTests extends \PHPUnit_Framework_TestCase
{
    /**
     * Test CommandInfo command annotation parsing.
     */
    function testAnnotatedCommandCreation()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testArithmatic');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:arithmatic', $command->getName());
        $this->assertEquals('This is the test:arithmatic command', $command->getDescription());
        $this->assertEquals("This command will add one and two. If the --negate flag\nis provided, then the result is negated.", $command->getHelp());
        $this->assertEquals('arithmatic', implode(',', $command->getAliases()));
        $this->assertEquals('test:arithmatic [--negate] [--] <one> <two>', $command->getSynopsis());
        $this->assertEquals('test:arithmatic 2 2 --negate', implode(',', $command->getUsages()));

        $input = new StringInput('arithmatic 2 3 --negate');
        $this->assertRunCommandViaApplicationEquals($command, $input, '-5');
    }

    function testMyCatCommand()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'myCat');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('my:cat', $command->getName());
        $this->assertEquals('This is the my:cat command', $command->getDescription());
        $this->assertEquals("This command will concatenate two parameters. If the --flip flag\nis provided, then the result is the concatenation of two and one.", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        $this->assertEquals('my:cat [--flip] [--] <one> [<two>]', $command->getSynopsis());
        $this->assertEquals('my:cat bet alpha --flip', implode(',', $command->getUsages()));

        $input = new StringInput('my:cat bet alpha --flip');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'alphabet');
    }

    function testDefaultsCommand()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'defaults');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('defaults', $command->getName());
        $this->assertEquals('Test default values in arguments', $command->getDescription());

        $input = new StringInput('defaults');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'nothing provided');

        $input = new StringInput('defaults ichi');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'only ichi');

        $input = new StringInput('defaults I II');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'I and II');
    }

    function testCommandWithNoOptions()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'commandWithNoOptions');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('command:with-no-options', $command->getName());
        $this->assertEquals('This is a command with no options', $command->getDescription());
        $this->assertEquals("This command will concatenate two parameters.", $command->getHelp());
        $this->assertEquals('nope', implode(',', $command->getAliases()));
        $this->assertEquals('command:with-no-options <one> [<two>]', $command->getSynopsis());
        $this->assertEquals('command:with-no-options alpha bet', implode(',', $command->getUsages()));

        $input = new StringInput('command:with-no-options something');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'somethingdefault');
    }

    function testCommandWithNoArguments()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'commandWithNoArguments');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('command:with-no-arguments', $command->getName());
        $this->assertEquals('This command has no arguments--only options', $command->getDescription());
        $this->assertEquals("Return a result only if not silent.", $command->getHelp());
        $this->assertEquals('command:with-no-arguments [-s|--silent]', $command->getSynopsis());

        $input = new StringInput('command:with-no-arguments');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Hello, world');
        $input = new StringInput('command:with-no-arguments -s');
        $this->assertRunCommandViaApplicationEquals($command, $input, '');
        $input = new StringInput('command:with-no-arguments --silent');
        $this->assertRunCommandViaApplicationEquals($command, $input, '');
    }

    function testCommandWithShortcutOnAnnotation()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'shortcutOnAnnotation');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('shortcut:on-annotation', $command->getName());
        $this->assertEquals('Shortcut on annotation', $command->getDescription());
        $this->assertEquals("This command defines the option shortcut on the annotation instead of in the options array.", $command->getHelp());
        $this->assertEquals('shortcut:on-annotation [-s|--silent]', $command->getSynopsis());

        $input = new StringInput('shortcut:on-annotation');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Hello, world');
        $input = new StringInput('shortcut:on-annotation -s');
        $this->assertRunCommandViaApplicationEquals($command, $input, '');
        $input = new StringInput('shortcut:on-annotation --silent');
        $this->assertRunCommandViaApplicationEquals($command, $input, '');
    }

    function testState()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile('secret secret');
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testState');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:state', $command->getName());

        $input = new StringInput('test:state');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'secret secret');
    }

    function testPassthroughArray()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testPassthrough');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:passthrough', $command->getName());

        $input = new StringInput('test:passthrough a b c');
        $input = new PassThroughArgsInput(['x', 'y', 'z'], $input);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'a,b,c,x,y,z');
    }

    function testPassThroughNonArray()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'myCat');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $input = new StringInput('my:cat bet --flip');
        $input = new PassThroughArgsInput(['x', 'y', 'z'], $input);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'x y zbet');
        // Can't look at 'hasOption' until after the command initializes the
        // option, because Symfony.
        $this->assertTrue($input->hasOption('flip'));
    }

    function testPassThroughWithInputManipulation()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'myRepeat');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $input = new StringInput('my:repeat bet --repeat=2');
        $input = new PassThroughArgsInput(['x', 'y', 'z'], $input);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'betx y zbetx y z');
        // Symfony does not allow us to manipulate the options via setOption until
        // the definition from the command object has been set up.
        $input->setOption('repeat', 3);
        $this->assertEquals(3, $input->getOption('repeat'));
        $input->setArgument(0, 'q');
        // Manipulating $input does not work -- the changes are not effective.
        // The end result here should be 'qx y yqx y yqx y y'
        $this->assertRunCommandViaApplicationEquals($command, $input, 'betx y zbetx y z');
    }

    function testHookedCommand()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $commandFactory->createCommandInfo($commandFileInstance, 'hookTestHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter test:hook', $hookInfo->getAnnotation('hook'));

        $commandFactory->registerCommandHook($hookInfo, $commandFileInstance);

        $hookCallback = $commandFactory->hookManager()->get('test:hook', 'alter');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestHook', $hookCallback[0][1]);

        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testHook');
        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:hook', $command->getName());

        $input = new StringInput('test:hook bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, '<[bar]>');
    }

    function testHookAllCommands()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleHookAllCommandFile();
        $commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $commandFactory->createCommandInfo($commandFileInstance, 'alterAllCommands');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter', $hookInfo->getAnnotation('hook'));

        $commandFactory->registerCommandHook($hookInfo, $commandFileInstance);

        $hookCallback = $commandFactory->hookManager()->get('Consolidation\TestUtils\ExampleHookAllCommandFile', 'alter');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('alterAllCommands', $hookCallback[0][1]);

        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'doCat');
        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('do:cat', $command->getName());

        $input = new StringInput('do:cat bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, '*** bar ***');
    }

    function testAnnotatedHookedCommand()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $commandFactory->createCommandInfo($commandFileInstance, 'hookTestAnnotatedHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter @hookme', $hookInfo->getAnnotation('hook'));

        $commandFactory->registerCommandHook($hookInfo, $commandFileInstance);
        $hookCallback = $commandFactory->hookManager()->get('@hookme', 'alter');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestAnnotatedHook', $hookCallback[0][1]);

        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testAnnotationHook');
        $annotationData = $commandInfo->getAnnotations();
        $this->assertEquals('hookme,before,after', implode(',', $annotationData->keys()));
        $this->assertEquals('@hookme,@before,@after', implode(',', array_map(function ($item) { return "@$item"; }, $annotationData->keys())));

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:annotation-hook', $command->getName());

        $input = new StringInput('test:annotation-hook baz');
        $this->assertRunCommandViaApplicationEquals($command, $input, '>(baz)<');
    }

    function testHookHasCommandAnnotation()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $commandFactory->createCommandInfo($commandFileInstance, 'hookAddCommandName');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter @addmycommandname', $hookInfo->getAnnotation('hook'));

        $commandFactory->registerCommandHook($hookInfo, $commandFileInstance);
        $hookCallback = $commandFactory->hookManager()->get('@addmycommandname', 'alter');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookAddCommandName', $hookCallback[0][1]);

        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'alterMe');
        $annotationData = $commandInfo->getAnnotations();
        $this->assertEquals('command,addmycommandname', implode(',', $annotationData->keys()));

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('alter-me', $command->getName());

        $input = new StringInput('alter-me');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'splendiferous from alter-me');

        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'alterMeToo');
        $annotationData = $commandInfo->getAnnotations();
        $this->assertEquals('addmycommandname', implode(',', $annotationData->keys()));
        $annotationData = $commandInfo->getAnnotationsForCommand();
        $this->assertEquals('addmycommandname,command', implode(',', $annotationData->keys()));

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('alter:me-too', $command->getName());

        $input = new StringInput('alter:me-too');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'fantabulous from alter:me-too');
    }


    function testHookedCommandWithHookAddedLater()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testHook');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:hook', $command->getName());

        // Run the command once without the hook
        $input = new StringInput('test:hook foo');
        $this->assertRunCommandViaApplicationEquals($command, $input, '[foo]');

        // Register the hook and run the command again
        $hookInfo = $commandFactory->createCommandInfo($commandFileInstance, 'hookTestHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter test:hook', $hookInfo->getAnnotation('hook'));

        $commandFactory->registerCommandHook($hookInfo, $commandFileInstance);
        $hookCallback = $commandFactory->hookManager()->get('test:hook', 'alter');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestHook', $hookCallback[0][1]);

        $input = new StringInput('test:hook bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, '<[bar]>');
    }

    function testInteractAndValidate()
    {
        $commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $commandFactory->createCommandInfo($commandFileInstance, 'interactTestHello');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals($hookInfo->getAnnotation('hook'), 'interact test:hello');

        $commandFactory->registerCommandHook($hookInfo, $commandFileInstance);

        $hookInfo = $commandFactory->createCommandInfo($commandFileInstance, 'validateTestHello');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals($hookInfo->getAnnotation('hook'), 'validate test:hello');

        $commandFactory->registerCommandHook($hookInfo, $commandFileInstance);

        $hookCallback = $commandFactory->hookManager()->get('test:hello', 'validate');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('validateTestHello', $hookCallback[0][1]);

        $hookCallback = $commandFactory->hookManager()->get('test:hello', 'interact');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('interactTestHello', $hookCallback[0][1]);

        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testHello');
        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:hello', $command->getName());
        $commandGetNames = $this->callProtected($command, 'getNames');
        $this->assertEquals('test:hello,Consolidation\TestUtils\ExampleCommandFile', implode(',', $commandGetNames));

        $testInteractInput = new StringInput('test:hello');
        $definition = new \Symfony\Component\Console\Input\InputDefinition(
            [
                new \Symfony\Component\Console\Input\InputArgument('application', \Symfony\Component\Console\Input\InputArgument::REQUIRED),
                new \Symfony\Component\Console\Input\InputArgument('who', \Symfony\Component\Console\Input\InputArgument::REQUIRED),
            ]
        );
        $testInteractInput->bind($definition);
        $testInteractOutput = new BufferedOutput();
        $command->commandProcessor()->interact(
            $testInteractInput,
            $testInteractOutput,
            $commandGetNames,
            $command->getAnnotationData()
        );
        $this->assertEquals('Goofey', $testInteractInput->getArgument('who'));

        $hookCallback = $command->commandProcessor()->hookManager()->get('test:hello', 'interact');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals('interactTestHello', $hookCallback[0][1]);

        $input = new StringInput('test:hello "Mickey Mouse"');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Hello, Mickey Mouse.');

        $input = new StringInput('test:hello');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Hello, Goofey.');

        $input = new StringInput('test:hello "Donald Duck"');
        $this->assertRunCommandViaApplicationEquals($command, $input, "I won't say hello to Donald Duck.", 1);

        $input = new StringInput('test:hello "Drumph"');
        $this->assertRunCommandViaApplicationEquals($command, $input, "Irrational value error.", 1);

        // Try the last test again with a display error function installed.
        $commandFactory->commandProcessor()->setDisplayErrorFunction(
            function ($output, $message) {
                $output->writeln("*** $message ****");
            }
        );

        $input = new StringInput('test:hello "Drumph"');
        $this->assertRunCommandViaApplicationEquals($command, $input, "*** Irrational value error. ****", 1);
    }

    function callProtected($object, $method, $args = [])
    {
        $r = new \ReflectionMethod($object, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($object, $args);
    }

    function assertRunCommandViaApplicationEquals($command, $input, $expectedOutput, $expectedStatusCode = 0)
    {
        $output = new BufferedOutput();

        $application = new Application('TestApplication', '0.0.0');
        $application->setAutoExit(false);
        $application->add($command);

        $statusCode = $application->run($input, $output);
        $commandOutput = trim($output->fetch());

        $this->assertEquals($expectedOutput, $commandOutput);
        $this->assertEquals($expectedStatusCode, $statusCode);
    }
}
