<?php
/*
 * This file is part of the GetOptionKit package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace GetOptionKit;
use GetOptionKit\Option;
use GetOptionKit\OptionResult;
use GetOptionKit\Argument;
use GetOptionKit\OptionParser;
use GetOptionKit\OptionCollection;
use Exception;
use GetOptionKit\Exception\InvalidOptionException;
use GetOptionKit\Exception\RequireValueException;

/**
 * A common command line argument format:
 *
 *      app.php
 *         [--app-options]
 *
 *      [subcommand
 *          --subcommand-options]
 *      [subcommand
 *          --subcommand-options]
 *      [subcommand
 *          --subcommand-options]
 *
 *      [arguments]
 *
 * ContinuousOptionParser is for the process flow:
 *
 * init app options,
 * parse app options
 *
 *
 *
 * while not end
 *   if stop at command
 *     shift command
 *     parse command options
 *   else if stop at arguments
 *     shift arguments
 *     execute current command with the arguments.
 *
 *  Example code:
 *
 *
 *      // subcommand stack
 *      $subcommands = array('subcommand1','subcommand2','subcommand3');
 *
 *      // different command has its own options
 *      $subcommand_specs = array(
 *          'subcommand1' => $cmdspecs,
 *          'subcommand2' => $cmdspecs,
 *          'subcommand3' => $cmdspecs,
 *      );
 *
 *      // for saved options
 *      $subcommand_options = array();
 *
 *      // command arguments
 *      $arguments = array();
 * 
 *      $argv = explode(' ','-v -d -c subcommand1 -a -b -c subcommand2 -c subcommand3 arg1 arg2 arg3');
 *
 *      // parse application options first
 *      $parser = new ContinuousOptionParser( $appspecs );
 *      $app_options = $parser->parse( $argv );
 *      while( ! $parser->isEnd() ) {
 *          if( $parser->getCurrentArgument() == $subcommands[0] ) {
 *              $parser->advance();
 *              $subcommand = array_shift( $subcommands );
 *              $parser->setSpecs( $subcommand_specs[$subcommand] );
 *              $subcommand_options[ $subcommand ] = $parser->continueParse();
 *          } else {
 *              $arguments[] = $parser->advance();
 *          }
 *      }
 *
 *
 **/
class ContinuousOptionParser extends OptionParser
{
    public $index;
    public $length;
    public $argv;

    /* for the constructor , the option specs is application options */
    public function __construct(OptionCollection $specs)
    {
        parent::__construct($specs);
        $this->index = 1;
    }

    public function startFrom($index)
    {
        $this->index = $index;
    }

    public function isEnd()
    {
        # echo "!! {$this->index} >= {$this->length}\n";
        return ($this->index >= $this->length);
    }

    public function advance()
    {
        $arg = $this->argv[ $this->index++ ];
        return $arg;
    }

    public function getCurrentArgument()
    {
        return $this->argv[ $this->index ];
    }

    public function continueParse()
    {
        return $this->parse($this->argv);
    }

    public function parse(array $argv)
    {
        // create new Result object.
        $result = new OptionResult;
        $argv = $this->preprocessingArguments($argv);

        $this->argv = $argv;
        $this->length = count($argv);

        // register option result from options with default value 
        foreach ($this->specs as $spec) {
            if ($spec->defaultValue !== null) {
                $result->set($spec->getId() , $spec);
            }
        }

        if ($this->isEnd()) {
            return $result;
        }


        // from last parse index
        for ( ; $this->index < $this->length; ++$this->index ) 
        {
            $arg = new Argument( $argv[$this->index] );

            /* let the application decide for: command or arguments */
            if (! $arg->isOption()) {
                # echo "stop at {$this->index}\n";
                return $result;
            }

            // if the option is with extra flags,
            //   split it out, and insert into the argv array
            //
            //   like -abc
            if ($arg->withExtraFlagOptions() ) {
                $extra = $arg->extractExtraFlagOptions();
                array_splice( $argv, $this->index + 1, 0, $extra );
                $argv[$this->index] = $arg->arg; // update argument to current argv list.
                $len = count($argv);   // update argv list length
            }

            $next = null;
            if ($this->index + 1 < count($argv) )  {
                $next = new Argument( $argv[$this->index + 1] );
            }

            $spec = $this->specs->get( $arg->getOptionName() );
            if (! $spec) {
                throw new InvalidOptionException("Invalid option: " . $arg );
            }

            if ($spec->isRequired()) {
                if ( ! $this->foundRequireValue($spec,$arg,$next) ) {
                    throw new RequireValueException( "Option '{$arg->getOptionName()}' requires a value." );
                }

                $this->takeOptionValue($spec,$arg,$next);
                if ($next && ! $next->anyOfOptions($this->specs)) {
                    $this->index++;
                }
                $result->set($spec->getId(), $spec);
            } elseif ($spec->isMultiple()) 
            {
                $this->pushOptionValue($spec,$arg,$next);
                if ($next && ! $next->anyOfOptions($this->specs) ) {
                    $this->index++;
                }
                $result->set( $spec->getId() , $spec);
            } elseif ($spec->isOptional())
            {
                $this->takeOptionValue($spec,$arg,$next);
                if (($spec->value || $spec->defaultValue) && $next && ! $next->anyOfOptions($this->specs)) {
                    $this->index++;
                }
                $result->set($spec->getId() , $spec);
            } elseif ($spec->isFlag()) {
                $spec->setValue(true);
                $result->set( $spec->getId() , $spec);
            } else 
            {
                throw new Exception('Unknown attribute.');
            }
        }
        return $result;
    }

}
