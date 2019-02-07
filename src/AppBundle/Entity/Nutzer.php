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
    

namespace AppBundle\Entity;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;


use Doctrine\ORM\Mapping\AttributeOverride;
use Doctrine\ORM\Mapping\AttributeOverrides;

//use Symfony\Component\Security\Core\User\UserInterface;


//class Nutzer implements UserInterface,  \Serializable
/**
 * @ORM\Entity
 * @ORM\Table(name="ams_Nutzer")
 * @AttributeOverrides({
 *      @AttributeOverride(name="username",
 *          column=@ORM\Column(options={"collation"="utf8_bin"})
 *          
 *      ),  
 * })
 */
class Nutzer extends BaseUser  
{
    /**
     * @ORM\Id
     * @ORM\Column(type="smallint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string",nullable=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $language;


    /**
     * @ORM\Column(type="boolean")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $notifyCaseCreation = false;


    public function __construct()
    {
        parent::__construct(); 
    }
    // PHP doesnt Provide method overloading
    public function SetCustom($name, $email, $role, $saltedpassword)
    {
        $this->setUsername($name);
        $this->setUsernameCanonical(strtolower($name));
        $this->setEmail($email);
        $this->setEmailCanonical(strtolower($email));
        $this->setEnabled(true);
        $this->addRole($role);
        $this->setPassword($saltedpassword);
    }
    
    public function setLanguage($newlanguage){
        $this->language = $newlanguage;
    }
    
    public function getLanguage(){
        return $this->language;
    }
    
    public function getEnabled(){
        return $this->enabled;
    }
    
    public function setNotifyCaseCreation($bool){
        $this->notifyCaseCreation = $bool;
    }
    
    public function getNotifyCaseCreation(){
        return $this->notifyCaseCreation;
    }
    
    public function getId(){
        return $this->id;
    }
    
   

}
