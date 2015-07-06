<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Hydra\PagedCollection;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DummyControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/api/1.0/city/suggest/{city}',
            function ($city) {
                return (new JsonResponse())->setContent(
                '[{"cid":"3000_LEUVEN","name":"Leuven","zip":"3000","cityId":"3000_Leuven","cityLabel":"3000 Leuven"}]');
            }
        );

        $controllers->get(
            '/api/1.0/location/suggest/{query}/{postalCode}',
            function ($query, $postalCode) {
                if ($postalCode == '3000' && $query == "Dep") {
                    return (new JsonResponse())->setContent(
                        '[{"id":"22db6f6e-a944-4ecc-a002-74f216294f45","title":"Het Depot"}]'
                    );
                }
                else {
                    return (new JsonResponse())->setContent('[]');
                }
            }
        );

        $controllers->get(
            '/places',
            function (Request $request) {
                $members = [];

                if ($request->query->get('q') === 'zipcode:3000') {
                    $members = [
                        json_decode('{"@id":"http:\/\/culudb-silex.dev:8080\/place\/7540A176-F9DE-A04E-D0592C7E3006528C","@context":"\/api\/1.0\/place.jsonld","description":"Voedselteams vzw ijvert voor een duurzame en eerlijke landbouwpolitiek door het bevorderen van kleinschalige, duurzame land-en tuinbouw en het stimuleren van een streekgebonden economie. Daartoe begeleidt en ondersteunt Voedselteams sociale netwerken van solidaire consumenten en producenten. Voedselteams doet aan sensibilisering over het thema met als doel mensen aan te zetten tot actie.","name":"Voedselteams","address":{"addressCountry":"BE","addressLocality":"Leuven","postalCode":"3000","streetAddress":"Blijde Inkomststraat 50"},"bookingInfo":{"description":"","name":"standard price","price":0,"priceCurrency":"EUR"},"terms":[{"label":"Locatie","domain":"actortype","id":"8.15.0.0.0"},{"label":"Organisator(en)","domain":"actortype","id":"8.11.0.0.0"},{"label":"Uitvoerder(s)","domain":"actortype","id":"8.0.0.0.0"},{"label":"Educatieve dienstverlening","domain":"facility","id":"3.14.0.0.0"},{"label":"Wijk of buurt","domain":"publicscope","id":"6.0.0.0.0"}]}'),
                        json_decode('
{"@id":"http:\/\/culudb-silex.dev:8080\/place\/56AF6D44-0DDA-76D4-2F5EE4184024FD78","@context":"\/api\/1.0\/place.jsonld","description":"curieus is de nieuwe naam voor CSC-vormingswerk.\ncurieus kom je tegen op meer dan 300 plaatsen in Vlaanderen en Brussel. cultuur in de breedste zin van het woord is ons handelsmerk. curieus wil je nieuwsgierig maken naar elke vorm van cultuur. Want cultuur is meer dan schone kunsten alleen. Cultuur kan meer dan mensen vermaken. Van Arbeidscultuur tot Zapcultuur.","name":"curieus Vlaams-Brabant","address":{"addressCountry":"BE","addressLocality":"Leuven","postalCode":"3000","streetAddress":"Mechelsestraat 70"},"bookingInfo":{"description":"","name":"standard price","price":0,"priceCurrency":"EUR"},"terms":[{"label":"Locatie","domain":"actortype","id":"8.15.0.0.0"},{"label":"Organisator(en)","domain":"actortype","id":"8.11.0.0.0"},{"label":"Uitvoerder(s)","domain":"actortype","id":"8.0.0.0.0"},{"label":"Wijk of buurt","domain":"publicscope","id":"6.0.0.0.0"}]}'),
                        json_decode('
{"@id":"http:\/\/culudb-silex.dev:8080\/place\/429A87B3-E3B7-697C-5C94A5159389EF25","@context":"\/api\/1.0\/place.jsonld","description":"Bibliotheek De Bib Leuven is een bruisende plek waar heel wat te beleven valt. Evenementen, lezingen, tentoonstellingen en workshops bewijzen dat de bibliotheek leeft.  De bib heeft een aparte exporuimte en aan de onthaalbalie kan je ook tickets kopen voor voorstellingen van 30CC. Daarnaast werd in hetzelfde gebouw ook het stadsarchief ondergebracht. Documenten kan je raadplegen in de leeszaal.","name":"De Bib Leuven","address":{"addressCountry":"BE","addressLocality":"Leuven","postalCode":"3000","streetAddress":"Rijschoolstraat 4"},"bookingInfo":{"description":"","name":"standard price","price":0,"priceCurrency":"EUR"},"terms":[{"label":"Locatie","domain":"actortype","id":"8.15.0.0.0"},{"label":"Organisator(en)","domain":"actortype","id":"8.11.0.0.0"},{"label":"Lokaal","domain":"publicscope","id":"6.1.0.0.0"},{"label":"Bibliotheek","domain":"actortype","id":"8.1.0.0.0"}]}'),
                        json_decode('{"@id":"http:\/\/culudb-silex.dev:8080\/place\/5023e3af-3fe1-45be-8a72-86ebe9ffa2fe","@context":"\/api\/1.0\/place.jsonld","description":"NB","name":"Cinema ZED","address":{"addressCountry":"BE","addressLocality":"Leuven","postalCode":"3000","streetAddress":"Naamsestraat 96"},"bookingInfo":{"description":"","name":"standard price","price":0,"priceCurrency":"EUR"},"terms":[{"label":"Locatie","domain":"actortype","id":"8.15.0.0.0"},{"label":"Organisator(en)","domain":"actortype","id":"8.11.0.0.0"},{"label":"Wijk of buurt","domain":"publicscope","id":"6.0.0.0.0"},{"label":"Bioscoop","domain":"actortype","id":"8.9.1.0.0"}]}'),
                    ];
                }

                $pagedCollection = new PagedCollection(
                    1,
                    1000,
                    $members,
                    count($members)
                );

                return (new JsonLdResponse($pagedCollection));
            }
        );

        return $controllers;
    }

}
