<div class="delivery-map_container">
    <div class="delivery-map_close">
        <svg width="8" height="11" viewBox="0 0 8 11" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M2.46195 5.5L7.99832 0.690857L7.33003 0L0.998322 5.5L7.33003 11L7.99832 10.3091L2.46195 5.5Z" fill="#FE4A5B"/>
        </svg>Вернуться к оформлению
    </div>
    <div class="delivery-map_search"></div>
    <div class="delivery-map_select">
        <div class="delivery-map_select-title">
            Пункты самовывоза
        </div>
        <div id="delivery-map_select-items">

        </div>
    </div>
    <div id="delivery-map-init"></div>
</div>
<?
$coordsJson = [];
foreach($deliveryChecked as $point):
    $coord = [];
    if(!$point['Id']) {continue;};
    $coord['id'] = $point['Id'];
    
    if($point['Lat']) {
        $coord['lat'] = $point['Lat'];
    } elseif($point['Latitude']) {
        $coord['lat'] = $point['Latitude'];
    } else {
        continue;
    };

    if($point['Long']) {
        $coord['lon'] = $point['Long'];
    } elseif($point['Longitude']) {
        $coord['lon'] = $point['Longitude'];
    } else {
        continue;
    };

    if(!$point['Name']) {continue;}
    $coord['name'] = $point['Name'];
    $coord['address'] = $point['Address'];

    $coordsJson[] = [
        'type' => "Feature",
        'id' => $coord['id'],
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [
                $coord['lat'],
                $coord['lon']
            ]
        ],
        'properties' => [
            'hintContent' => $coord['name'],
            'balloonContent' => $coord['address']
        ],
        'options' => [
            'preset' => "islands#redCircleIcon"
        ]
    ];
    unset($coord);
endforeach;
$coordsJson = json_encode($coordsJson);
?>
<script>
ymaps.ready(function () {
        var myMap = new ymaps.Map('delivery-map-init', {
                center: [
                    <?=($arResult['ADDRESS']['LAT'])?:'55.751574'?>,
                    <?=($arResult['ADDRESS']['LON'])?:'37.573856'?>
                ],
                zoom: 12,
                controls: ['geolocationControl', 'zoomControl']
            }, {
                geolocationControlFloat: 'right',
                zoomControlSize: 'large'
            }),
        
            placemarkHouse = new ymaps.Placemark([
                <?=($arResult['ADDRESS']['LAT'])?:'55.751574'?>,
                <?=($arResult['ADDRESS']['LON'])?:'37.573856'?>
            ], {
                hintContent: 'Адрес доставки'
            }, {
                preset: 'islands#redHomeCircleIcon'
            });
        var objectManager = new ymaps.ObjectManager({
            clusterize: true,
            clusterDisableClickZoom: true,
            clusterHasBalloon: false,
            clusterHasHint:false
        });
        objectManager.add(<?=$coordsJson;?>);
        //objectManager.objects.options.set('preset', 'islands#redDotIcon');
        objectManager.clusters.options.set('preset', 'islands#invertedRedClusterIcons');
        myMap.geoObjects
            .add(placemarkHouse);
        myMap.geoObjects
            .add(objectManager);
        objectManager.objects.events.add(['click'], function(e){
            var objectId = e.get('objectId');
            object = objectManager.objects.getById(objectId);
            html = '';
            if ((!object.properties) || (!object.properties.balloonContent)) {
                console.log("Для объекта с идентификатором " + object.id + " не задано содержимое балуна.");
            } else {
                var item = object.properties;
                html += '<div class="item">';
                html += '<div class="item-name">'+item.hintContent+'</div>';
                html += '<div class="item-address">'+item.balloonContent+'</div>';
                html += '<button data-id="'+object.id+'" class="btn_multi_pickpoint">Забрать отсюда</button>';
                html += '</div>';
            }
            BX('delivery-map_select-items').innerHTML = html;
        });
        objectManager.clusters.events.add(['click'], function(e){
            var objectId = e.get('objectId');
            cluster = objectManager.clusters.getById(objectId);
            html = '';
            cluster.features.forEach(function(object){
                if ((!object.properties) || (!object.properties.balloonContent)) {
                    console.log("Для объекта с идентификатором " + object.id + " не задано содержимое балуна.");
                } else {
                    var item = object.properties;
                    html += '<div class="item">';
                    html += '<div class="item-name">'+item.hintContent+'</div>';
                    html += '<div class="item-address">'+item.balloonContent+'</div>';
                    html += '<button data-id="'+object.id+'" class="btn_multi_pickpoint">Забрать отсюда</button>';
                    html += '</div>';
                }
            });
            BX('delivery-map_select-items').innerHTML = html;
        });
    
    });
</script>