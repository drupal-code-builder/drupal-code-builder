<?php

namespace DrupalCodeBuilder\PhpCsFixer;

use PhpCsFixer\Config;
use PhpCsFixer\Console\ConfigurationResolver;
use PhpCsFixer\Error\ErrorsManager;
use PhpCsFixer\Runner\Runner;
use PhpCsFixer\ToolInfo;

class VariableCodeProcessor {

    public $protocol = 'phpcsfixer';
    public $config;

    /**
     * @param string|Config|null $config A path to configuration file or a config object
     */
    public function __construct( string|Config $config=null) {
        $this->config = $config;
        $existed = in_array( $this->protocol, stream_get_wrappers() );
        if ( $existed ) {
            stream_wrapper_unregister( $this->protocol );
        }
        stream_wrapper_register( $this->protocol, VariableStream::class );
    }

    public function setConfig($config) {
        $this->config = $config;
    }

    public function getFromPath( $phpFilePath ) {
        return $this->get( file_get_contents( $phpFilePath ) );
    }

    public function get( $codeToFix ) {
        $pathVirtual = $this->protocol . '://' . uniqid() . '.virtual.php'; // .virtual.php is checked with VariableStream
        file_put_contents( $pathVirtual, $codeToFix );
        $options     = [
            // 'allow-risky' => null,
            // 'config' => $passedConfig,
            'dry-run'           => false,
            // 'rules' => $passedRules,
            'path'              => [ $pathVirtual ],
            // 'path-mode' => null,
            'using-cache'       => 'no',
            // 'cache-file' => null,
            // 'format' => null,
            // 'diff' => null,
            'stop-on-violation' => true,
            // 'verbosity' => $verbosity,
            // 'show-progress' => null,
        ];
        if ( is_string( $this->config ) && file_exists( $this->config ) ) {
            $options[ 'config' ] = $this->config;
        }
        $resolver = new ConfigurationResolver(
            ( $this->config instanceof Config ) ? $this->config : new Config( 'default' ),
            $options,
            '',
            new ToolInfo()
        );

        $runner = new Runner(
            new \ArrayIterator( [ new VirtualFileInfo( $pathVirtual ) ] ),
            $resolver->getFixers(),
            $resolver->getDiffer(),
            null,
            new ErrorsManager(),
            $resolver->getLinter(),
            $resolver->isDryRun(),
            $resolver->getCacheManager(),
            $resolver->getDirectory(),
            $resolver->shouldStopOnViolation()
        );
        $runner->fix();
        $fixedCode = file_get_contents( $pathVirtual );
        file_put_contents( $pathVirtual, '' );
        return $fixedCode;

    }

}