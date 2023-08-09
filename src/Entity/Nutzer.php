<?php

namespace App\Entity;

use App\Repository\NutzerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: NutzerRepository::class)]
#[ORM\Table(name: "ams_Nutzer")]
#[AttributeOverrides([
    new AttributeOverride(name:"username", column: new ORM\Column(options:[collation => "utf8_bin"]))

])]

class Nutzer implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;


    #[ORM\Column(length: 180, unique: true)]
    private ?string $fullname = null;


    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }


    
    #[ORM\Column(type: "string",nullable:true)]
    #[ORM\GeneratedValue(strategy:"AUTO")]
    protected $language;


    
    #[ORM\Column(type:"boolean")]
    #[ORM\GeneratedValue(strategy:"AUTO")]
    protected $notifyCaseCreation = false;

    #[ORM\Column(type:"boolean")]
    #[ORM\GeneratedValue(strategy:"AUTO")]
    protected $enabled = true;


    // Benoetigt zur temporaren Verarbeitung des Passwords
    // wird auch nicht in die Datenbank gespeichert
    private $plainPassword;



    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }



    public function getFullname(): string
    {
        return (string) $this->fullname;
    }

    public function setFullname(string $newfullname): static
    {
        $this->fullname = $newfullname;

        return $this;
    }




    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }


    // PHP doesnt Provide method overloading
    public function SetCustom($name, $fullname, $email, $role, $saltedpassword)
    {
        $this->setUsername($name);
        $this->setFullname($fullname);
        $this->setEmail($email);
        #$this->setEnabled(true);
        $this->setRoles($role);
        $this->setPassword($saltedpassword);
    }
    
    public function setLanguage($newlanguage){
        $this->language = $newlanguage;
    }

    public function setEMail($newemail){
        $this->email = $newemail;
    }

    public function getEMail(){
        return $this->email;
    }
    
    public function getLanguage(){
        return $this->language;
    }
    
    
    public function setNotifyCaseCreation($bool){
        $this->notifyCaseCreation = $bool;
    }
    
    public function getNotifyCaseCreation(){
        return $this->notifyCaseCreation;
    }

    public function __toString(){
        return $this->username;

    }
    

    public function getEnabled(){
        return $this->enabled;
    }

    public function isEnabled(){
        return $this->enabled;
    }

    public function setEnabled($newstatus){
        $this->enabled = $newstatus;
    }



    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }
    public function setPlainPassword(string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

}
