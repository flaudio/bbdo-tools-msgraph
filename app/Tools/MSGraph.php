<?php

namespace App\Tools;

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use App\TokenStore\TokenCache;

Class MSGraph {
        /*
        type:
            'resource' => return the base64 encoded binary data
            'tag' = <img src="[base64:photo]>"

        */
        public function getProfilePicture($graph_photo, $type = 'resource', $size=''){
            // Get the access token from the cache
            $tokenCache = new TokenCache();
            $accessToken = $tokenCache->getAccessToken();

            // Create a Graph client
            $graph = new Graph();
            $graph->setAccessToken($accessToken);

            $photo = $graph->createRequest("GET", "/me/photos/120x120/\$value")->execute();
            $photo = $photo->getRawBody();

            $meta = $graph->createRequest("GET", "/me/photo")->execute();
            $meta = $meta->getBody();

            $profile_photo_src = 'data:'.$meta['@odata.mediaContentType'].';base64,'.base64_encode($photo);

            switch ($type) {
                case 'resource':
                    return $profile_photo_src;
                    break;
                case 'tag':
                    return '<img src="'.$profile_photo_src.'">';
                    break;
                default:
                    return $profile_photo_src;
                    break;
            }


        }
}
