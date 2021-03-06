<?php
declare(strict_types = 1);
/**
 * Contains class YamlConfigFileSpec.
 *
 * PHP version 7.0
 *
 * LICENSE:
 * This file is part of Yet Another Php Eve Api Library also know as Yapeal
 * which can be used to access the Eve Online API data and place it into a
 * database.
 * Copyright (C) 2016 Michael Cummings
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see
 * <http://spdx.org/licenses/LGPL-3.0.html>.
 *
 * You should be able to find a copy of this license in the COPYING-LESSER.md
 * file. A copy of the GNU GPL should also be available in the COPYING.md file.
 *
 * @author    Michael Cummings <mgcummings@yahoo.com>
 * @copyright 2016 Michael Cummings
 * @license   LGPL-3.0
 */
namespace Spec\Yapeal\Cli\Yapeal;

use PhpSpec\ObjectBehavior;

//use Prophecy\Argument;
/**
 * Class YamlConfigFileSpec
 *
 * @mixin \Yapeal\Cli\Yapeal\YamlConfigFile
 *
 * @method void during($method, array $params)
 * @method void shouldBe($value)
 * @method void shouldContain($value)
 * @method void shouldNotEqual($value)
 * @method void shouldReturn($result)
 */
class YamlConfigFileSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Yapeal\Cli\Yapeal\YamlConfigFile');
    }
    public function it_should_return_self_from_set_path_file()
    {
        $this->setPathFile('')
            ->shouldReturn($this);
    }
    public function it_should_return_self_from_set_settings()
    {
        $this->setSettings([])
            ->shouldReturn($this);
    }
    public function it_throws_exception_in_get_path_file_when_not_set_yet()
    {
        $mess = 'Trying to access $pathFile before it was set';
        $this->shouldThrow(new \LogicException($mess))
            ->during('getPathFile');
    }
}
