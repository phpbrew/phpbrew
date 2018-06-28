<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Helper;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * HelperSet represents a set of helpers to be used with a command.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HelperSet implements \IteratorAggregate
{
    private $helpers = array();
    private $command;

    /**
     * Constructor.
     *
     * @param Helper[] $helpers An array of helper.
     */
    public function __construct(array $helpers = array())
    {
        foreach ($helpers as $alias => $helper) {
            $this->set($helper, is_int($alias) ? null : $alias);
        }
    }

    /**
     * Sets a helper.
     *
     * @param HelperInterface $helper The helper instance
     * @param string          $alias  An alias
     */
    public function set(HelperInterface $helper, $alias = null)
    {
        $this->helpers[$helper->getName()] = $helper;
        if (null !== $alias) {
            $this->helpers[$alias] = $helper;
        }

        $helper->setHelperSet($this);
    }

    /**
     * Returns true if the helper if defined.
     *
     * @param string $name The helper name
     *
     * @return bool true if the helper is defined, false otherwise
     */
    public function has($name)
    {
        return isset($this->helpers[$name]);
    }

    /**
     * Gets a helper value.
     *
     * @param string $name The helper name
     *
     * @return HelperInterface The helper instance
     *
     * @throws InvalidArgumentException if the helper is not defined
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('The helper "%s" is not defined.', $name));
        }

        if ('dialog' === $name && $this->helpers[$name] instanceof DialogHelper) {
            @trigger_error('"Symfony\Component\Console\Helper\DialogHelper" is deprecated since version 2.5 and will be removed in 3.0. Use "Symfony\Component\Console\Helper\QuestionHelper" instead.', E_USER_DEPRECATED);
        } elseif ('progress' === $name && $this->helpers[$name] instanceof ProgressHelper) {
            @trigger_error('"Symfony\Component\Console\Helper\ProgressHelper" is deprecated since version 2.5 and will be removed in 3.0. Use "Symfony\Component\Console\Helper\ProgressBar" instead.', E_USER_DEPRECATED);
        } elseif ('table' === $name && $this->helpers[$name] instanceof TableHelper) {
            @trigger_error('"Symfony\Component\Console\Helper\TableHelper" is deprecated since version 2.5 and will be removed in 3.0. Use "Symfony\Component\Console\Helper\Table" instead.', E_USER_DEPRECATED);
        }

        return $this->helpers[$name];
    }

    /**
     * Sets the command associated with this helper set.
     *
     * @param Command $command A Command instance
     */
    public function setCommand(Command $command = null)
    {
        $this->command = $command;
    }

    /**
     * Gets the command associated with this helper set.
     *
     * @return Command A Command instance
     */
    public function getCommand()
    {
        return $this->command;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->helpers);
    }
}
