<?php

namespace AgenDAV\Data;

/*
 * Copyright 2014 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

use \AgenDAV\DateHelper;

class Reminder
{
    /**
     * Stores the position for this reminder in the original
     * event
     *
     * @var integer
     */
    protected $position;

    /**
     * @var \DateInterval
     */
    protected $when;

    /**
     * @param \DateInterval $when
     * @param integer $position
     */
    public function __construct(\DateInterval $when, $position = null)
    {
        $this->when = $when;
        $this->position = $position;
    }

    /**
     * Parses an input array and returns a Reminder
     *
     * @param array $input Key-value based array. Expected keys are:
     *                     - count: number of <units>
     *                     - unit: one of minutes, hours or days
     *                     - position (optional)
     * @return AgenDAV\Data\Reminder
     */
    public static function createFromInput(array $input)
    {
        $string = $input['count'] . ' ' . $input['unit'];
        $interval = \DateInterval::createFromDateString($string);

        $position = !empty($input['position']) ? $input['position'] : null;

        return new self($interval, $position);
    }

    /**
     * Receives an iCalcreator VALARM and creates a new Reminder.
     *
     * If the VALARM is not supported by AgenDAV, a null value will be returned
     *
     * @param \valarm $valarm iCalcreator VALARM object
     * @param integer $position Position of this VALARM inside the VEVENT
     * @return AgenDAV\Data\Reminder|null
     */
    public static function createFromiCalcreator($valarm, $position)
    {
        $trigger = $valarm->getProperty('trigger');
        if ($trigger['relatedStart'] === false) {
            return null;
        }

        $units = [
            'min' => 1,
            'hour' => 60,
            'day' => 1440,
            'week' => 10080,
        ];

        $total_minutes = 0;
        foreach ($units as $unit => $minutes) {
            if (isset($trigger[$unit])) {
                $total_minutes += $trigger[$unit]*$minutes;
            }
        }

        // Discard this VALARM if its 'before' property is false and
        // it is triggered just on start
        if ($trigger['before'] === false && $total_minutes !== 0) {
            return null;
        }

        $used_unit = '';
        $count = 0;

        foreach ($units as $unit => $minutes) {
            if ($total_minutes % $minutes === 0) {
                $used_unit = $unit;
                $count = $total_minutes/$minutes;
                break;
            }
        }

        if ($used_unit === '') {
            return null;
        }

        $string = $count . ' ' . $used_unit;
        $interval = \DateInterval::createFromDateString($string);

        return new self($interval, $position);
    }

    public function generateVAlarm()
    {
        $valarm = new \valarm; // Ugghh

        list($count, $unit) = $this->getParsedWhen();

        $unit_names = [
            'minutes' => 'min',
            'hours' => 'hour',
            'days' => 'day',
            'weeks' => 'week',
            'months' => 'month',
        ];

        $valarm->setProperty(
            'trigger',
            [
                $unit_names[$unit] => $count,
                'relatedStart' => true,
                'before' => true,
            ]
        );
        $valarm->setProperty('action', 'DISPLAY');
        $valarm->setProperty('description', 'Reminder set in AgenDAV');

        return $valarm;
    }

    /**
     * Parses current date interval
     */
    public function getParsedWhen()
    {
        $dateinterval_units = [
            'i' => 1,
            'h' => 60,
            'd' => 1440,
            'm' => 40320,
        ];


        $count_minutes = 0;

        foreach ($dateinterval_units as $key => $minutes) {
            if ($this->when->{$key} !== 0) {
                $count_minutes = $this->when->{$key} * $minutes;
                break;
            }
        }

        if ($count_minutes === 0) {
            return [ 0, 'minutes' ];
        }

        $units = [
            'months' => 40320,
            'weeks' => 10080,
            'days' => 1440,
            'hours' => 60,
            'minutes' => 1,
        ];

        foreach ($units as $unit => $minutes) {
            if ($count_minutes % $minutes === 0) {
                $count = $count_minutes/$minutes;
                return [
                    $count,
                    $unit
                ];
            }
        }

        // What happened?
        return [99999, 'months'];
    }

    /*
     * Getter for position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /*
     * Getter for when
     *
     * @return \DateInterval
     */
    public function getWhen()
    {
        return $this->when;
    }
}
