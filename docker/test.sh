#bin/bash

#docker exec -it php.uitdatabank composer test -- --filter=$1
docker exec -it php.uitdatabank composer remove silex/silex --ignore-platform-reqs
