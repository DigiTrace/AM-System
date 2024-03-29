<?php

   // AM-System
   // Copyright (C) 2019 Robert Krasowski
   // This program was created during an internship at DigiTrace GmbH
   // Read LIZENZ.txt for full notice

   // This program is free software: you can redistribute it and/or modify
   // it under the terms of the GNU General Public License as published by
   // the Free Software Foundation, either version 3 of the License, or
   // (at your option) any later version.

   // This program is distributed in the hope that it will be useful,
   // but WITHOUT ANY WARRANTY; without even the implied warranty of
   // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   // GNU General Public License for more details.

   // You should have received a copy of the GNU General Public License
   // along with this program.  If not, see <http://www.gnu.org/licenses/>.


   namespace App\EventSubscriber;

   use Symfony\Component\EventDispatcher\EventSubscriberInterface;
   use Symfony\Component\HttpFoundation\RequestStack;
   use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
   use Symfony\Component\Security\Http\SecurityEvents;
   
   /**
    * Stores the locale of the user in the session after the
    * login. This can be used by the LocaleSubscriber afterwards.
    */
   class UserLocaleSubscriber implements EventSubscriberInterface
   {
       private $requestStack;
   
       public function __construct(RequestStack $requestStack)
       {
           $this->requestStack = $requestStack;
       }
   
       public function onInteractiveLogin(InteractiveLoginEvent $event)
       {
           $user = $event->getAuthenticationToken()->getUser();
   
           if (null !== $user->getLanguage()) {
               $this->requestStack->getSession()->set('_locale', $user->getLanguage());
           }
       }
   

        /**
         * @return array
        */
       public static function getSubscribedEvents()
       {
           return [
               SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
           ];
       }
   }
