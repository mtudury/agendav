<?php

namespace AgenDAV\Controller;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\DateHelper;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JavaScriptCode
{
    /**
     * Generates JavaScript code to provide the frontend the site configuration
     * and some user preferences
     *
     * @param Request $request
     * @param Application $app
     *
     * @return Response
     */
    public function settingsAction(Request $request, Application $app)
    {
        $site_config = $this->getSiteConfig($request, $app);

        $preferences = $this->getPreferences($request, $app);

        $response = new Response(
            $app['twig']->render('jsconfig.html', [
                'site_config' => $site_config,
                'preferences' => $preferences,
                'username' => $app['session']->get('username'),
            ])
        );

        $response->headers->set('Content-Type', 'text/javascript');
        $response->setPrivate();
        $response->mustRevalidate();
        $response->setExpires(new \DateTime);;
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * @param Request $request
     * @param Application $app
     *
     * @return array
     */
    protected function getSiteConfig(Request $request, Application $app)
    {
        $settings = [
            'base_url' => $request->getBasePath(),
            'base_app_url' => $request->getBaseUrl() . '/',
            'agendav_version' => \AgenDAV\Version::V,
            'enable_calendar_sharing' => $app['calendar.sharing'],
            'calendar_colors' => $app['calendar.colors'],
            'default_calendar_color' => '#' . $app['calendar.colors'][0],
            'show_public_caldav_url' => $app['caldav.publicurls'],
            'workflow_url' => $app['workflow.url'],
            'workflow_key' => sha1($app['workflow.key'] . $app['session']->get('username') . date("Ymd")),
        ];

        if ($app['caldav.publicurls']) {
            $settings['caldav_public_base_url'] = $app['caldav.baseurl.public'];
        }

        return $settings;
    }

    /**
     * @param Request $request
     * @param Application $app
     *
     * @return mixed
     */
    protected function getPreferences(Request $request, Application $app)
    {
        $preferences = $app['user.preferences'];

        return $preferences->getAll();
    }
}
