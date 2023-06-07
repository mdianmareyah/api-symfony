<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class VersioningService 
{
    private $requestStack;
    private $defaultVersion;

    /**
     * constructeur permettant de recuperer la requête courante(pour extraire le champ accept)
     * ainsi que le parameterBagInterface pour recuperer la version par défaut dans le fichier de configuration
     * 
     * @param RequestStack $requestStack
     * @param ParameterBagInterface $params
     */
    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->requestStack = $requestStack;
        $this->defaultVersion = $params->get('default_api_version');
    }

    /**
     * recuperation de la version qui a été envoyé dans le header 'accept' de la requête http
     * 
     * @return string : le numero de la version. Par défaut, la version rétournée est celle dans le fichier 
     * de configuration services.yaml: "default_api_version"
     */
    public function getVersion(): string
    {
        $version = $this->defaultVersion;
        $request = $this->requestStack->getCurrentRequest();
        $accept = $request->headers->get('Accept');

        $entetes = explode(';',$accept);

        foreach($entetes as $value) {
            if(strpos($value,'version') !== false)
            {
                $version = explode('=',$value);
                $version = $version[1];
                break;
            }
        }

        return $version;
    }


}