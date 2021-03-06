<?php

include_once(__DIR__.'/CommandTest.php');

use Symfony\Component\Console\Input\ArrayInput;

class GenerateTest extends CommandTest
{
    /**
     * @dataProvider provideGenerateParameters
     *
     * @param string|null $name
     * @param string|null $format
     * @param string|null $type
     * @param string|null $matchType
     * @param string|null $matchValue
     * @param bool $matchExcept
     * @param string|null $mode
     */
    public function testGenerateDSL(
        $name = null,
        $format = null,
        $type = null,
        $matchType = null,
        $matchValue = null,
        $matchExcept = false,
        $mode = null
    ) {
        $parameters = array(
            'command' => 'kaliop:migration:generate',
            'bundle' => $this->targetBundle
        );

        if ($name) {
            $parameters['name'] = $name;
        }

        if ($format) {
            $parameters['--format'] = $format;
        }

        if ($type) {
            $parameters['--type'] = $type;
        }

        if ($matchType) {
            $parameters['--match-type'] = $matchType;
        }

        if ($matchValue) {
            $parameters['--match-value'] = $matchValue;
        }

        if ($matchExcept) {
            $parameters['--match-except'] = null;
        }

        if ($mode) {
            $parameters['--mode'] = $mode;
        }

        $input = new ArrayInput($parameters);
        $exitCode = $this->app->run($input, $this->output);
        $output = $this->fetchOutput();
        $this->assertSame(0, $exitCode, 'CLI Command failed. Output: ' . $output);
        $this->checkGeneratedFile($this->saveGeneratedFile($output), $mode);
    }

    public function provideGenerateParameters()
    {
        return array(
            array(),
            array(null, 'sql'),
            array(null, 'php'),
            array('unit_test_generated'),
            array('unit_test_generated', 'sql'),
            array('unit_test_generated', 'php'),
            array('unit_test_generated_role', null, 'role', 'identifier', 'Anonymous'),
            array('unit_test_generated_role', 'yml', 'role', 'identifier', 'Anonymous', false, 'update'),
            array('unit_test_generated_role', 'json', 'role', 'identifier', 'Anonymous', false, 'delete'),
            array('unit_test_generated_role', 'yml', 'role', 'identifier', 'Anonymous,Administrator', true, 'delete'),
            array('unit_test_generated_role', 'json', 'role', 'all', null, false, 'create'),
            array('unit_test_generated_content_type', null, 'content_type', 'identifier', 'folder'),
            array('unit_test_generated_content_type', 'yml', 'content_type', 'identifier', 'folder', false, 'update'),
            array('unit_test_generated_content_type', 'json', 'content_type', 'identifier', 'folder', false, 'delete'),
            array('unit_test_generated_content_type', 'yml', 'content_type', 'identifier', 'folder,user', true, 'delete'),
            array('unit_test_generated_content_type', 'json', 'content_type', 'all', null, false, 'create'),
            array('unit_test_generated_content', null, 'content', 'contenttype_identifier', 'user_group'),
            array('unit_test_generated_content', 'yml', 'content', 'contenttype_identifier', 'user_group', false, 'update'),
            array('unit_test_generated_content', 'json', 'content', 'contenttype_identifier', 'user_group', false, 'delete'),
        );
    }

    protected function saveGeneratedFile($output)
    {
        if (preg_match('/Generated new migration file: +(.*)/', $output, $matches)) {
            $this->leftovers[] = $matches[1];
            return $matches[1];
        }

        return null;
    }

    protected function checkGeneratedFile($filePath, $mode)
    {
        $input = new ArrayInput(array('command' => 'kaliop:migration:status'));
        $exitCode = $this->app->run($input, $this->output);
        $output = $this->fetchOutput();
        $this->assertSame(0, $exitCode, 'CLI Command failed. Output: ' . $output);
        $this->assertRegExp('?\| ' . basename($filePath) . ' +\| not executed +\|?', $output);

        // We should really test generated migrations by executing them, but for the moment we have a few problems:
        // 1. we should patch them after generation, eg. replacing 'folder' w. something else (to be able to create and delete the content-type)
        // 2. generated migration for 'anon' user has a limitation with borked siteaccess
        // 3. generated migration for 'folder' contettype has a borked field-settings definition
        if (false) {
            $input = new ArrayInput(array('command' => 'kaliop:migration:migrate', '--path' => array($filePath), '-n' => null));
            $exitCode = $this->app->run($input, $this->output);
            $output = $this->fetchOutput();
            $this->assertSame(0, $exitCode, 'CLI Command failed. Output: ' . $output);
            $this->assertRegexp('?\| ' . basename($filePath) . ' +\| +\|?', $output);
        }
    }
}
