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
use GetOptionKit\OptionCollection;
use GetOptionKit\OptionResult;
use GetOptionKit\Argument;
use Exception;
use GetOptionKit\Exception\InvalidOptionException;
use GetOptionKit\Exception\RequireValueException;

class OptionParser 
{
    public $specs;
    public $longOptions;
    public $shortOptions;

    public function __construct(OptionCollection $specs)
    {
        $this->specs = $specs;
    }

    public function setSpecs(OptionCollection $specs)
    {
        $this->specs = $specs;
    }


    /* a helper to build option specification object from string spec 
     *
     * @param $specString string
     * @param $description string
     * @param $key
     *
     * */
    public function addSpec($specString, $description , $key = null ) 
    {
        $spec = $this->specs->add($specString,$description,$key);
        return $spec;
    }

    /* get option specification by Id */
    public function getSpec($id)
    {
        return $this->specs->get($id);
    }




    /* detect option */
    public function isOption($arg)
    {
        return substr($arg,0,1) === '-';
    }

    /* take option value from current argument or from the next argument */
    public function takeOptionValue(Option $spec, $arg, $next)
    {
        if ($next && !$next->anyOfOptions($this->specs)) {
            $spec->setValue($next->arg);
        } else if ($spec->defaultValue) {
            $spec->setValue($spec->defaultValue);
        } else if ($spec->isFlag()) {
            $spec->setValue(true);
        } else if ($next && !$next->isEmpty()) {
            $spec->setValue($next->arg);
        } else {
            $spec->setValue(true);
        }
    }

    /* 
     * push value to multipl value option
     */
    public function pushOptionValue(Option $spec,$arg,$next)
    {
        if ($next && ! $next->anyOfOptions($this->specs)) {
            $spec->pushValue( $next->arg );
        }
    }

    public function foundRequireValue(Option $spec,$arg,$next)
    {
        /* argument doesn't contain value and next argument is option */
        if ($next && ! $next->isEmpty() && !$next->anyOfOptions($this->specs)) {
            return true;
        }
        return false;
    }


    public function preprocessingArguments(array $argv)
    {
        // preprocessing arguments
        $newArgv = array();
        $afterDash = false;
        foreach($argv as $arg) {
            if ($arg == '--') {
                $afterDash = true;
            }
            if ($afterDash) {
                $newArgv[] = $arg;
                continue;
            }

            $a = new Argument($arg);
            if ($a->anyOfOptions($this->specs) && $a->containsOptionValue()) {
                list($opt,$val) = $a->splitAsOption();
                array_push($newArgv, $opt, $val);
            } else {
                array_push($newArgv, $arg);
            }
        }
        return $newArgv;
    }


    /**
     * @param array $argv
     * @return OptionResult|Option[]
     * @throws Exception\RequireValueException
     * @throws Exception\InvalidOptionException
     * @throws \Exception
     */
    public function parse(array $argv)
    {
        $result = new OptionResult;
        $argv = $this->preprocessingArguments($argv);


        foreach ($this->specs as $spec) {
            if ($spec->defaultValue !== null) {
                $result->set($spec->getId() , $spec);
            }
        }


        $len = count($argv);
        for ($i = 0; $i < $len; ++$i)
        {
            $arg = new Argument( $argv[$i] );

            // if looks like not an option, push it to argument list.
            // TODO: we might want to support argument with preceding dash (?)
            if (! $arg->isOption()) {
                $result->addArgument( $arg );
                continue;
            }

            // if the option is with extra flags,
            //   split the string, and insert into the argv array
            if ($arg->withExtraFlagOptions()) {
                $extra = $arg->extractExtraFlagOptions();
                array_splice( $argv, $i+1, 0, $extra );
                $argv[$i] = $arg->arg; // update argument to current argv list.
                $len = count($argv);   // update argv list length
            }


            $next = null;
            if ($i + 1 < count($argv) )  {
                $next = new Argument($argv[$i + 1]);
            }

            $spec = $this->specs->get($arg->getOptionName());
            if (! $spec) {
                throw new InvalidOptionException("Invalid option: " . $arg );
            }

            if ($spec->isRequired()) {
                if (! $this->foundRequireValue($spec, $arg, $next) ) {
                    throw new RequireValueException( "Option {$arg->getOptionName()} requires a value. given '{$next}'");
                }
                $this->takeOptionValue($spec, $arg, $next);
                if ($next && ! $next->anyOfOptions($this->specs)) {
                    $i++;
                }
                $result->set($spec->getId(), $spec);

            } 
            elseif ($spec->isMultiple()) 
            {
                $this->pushOptionValue($spec,$arg,$next);
                if ($next && $next->isOption()) {
                    $i++;
                }
                $result->set($spec->getId(), $spec);
            }
            elseif ($spec->isOptional())
            {
                $this->takeOptionValue($spec,$arg,$next);
                if (($spec->value || $spec->defaultValue) && $next && !$next->isOption() ) {
                    $i++;
                }
                $result->set( $spec->getId() , $spec);
            }
            elseif ($spec->isIncremental())
            {
                $spec->increaseValue();
                $result->set($spec->getId() , $spec);
            }
            elseif ($spec->isFlag())
            {
                $spec->setValue(true);
                $result->set($spec->getId() , $spec);
            }
            else
            {
                throw new Exception('Unknown attribute.');
            }
        }
        return $result;
    }
}
