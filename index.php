<?php declare(strict_types=1);

require_once 'vendor/autoload.php';
use Siler\Swoole;

use function Siler\Functional\puts;




\Swoole\Runtime::enableCoroutine();
$redis = new Redis;
$redis->connect('redis', 6379);
$redis->set('total_viwers',0);
$all_users_key = "allUsersKey";

$echo = function ($frame) use ($redis,$all_users_key){
    
    
    
    $message = json_decode($frame->data, true);
    echo $message['location'],"\n";
    switch ($message['type']) {
        case 'welcome':
            $redis->lPush($all_users_key,$message['location']);
            echo $redis->lrange($all_users_key, 0, -1);
            Swoole\broadcast(json_encode(['type'=>'welcome','value'=>$redis->lrange($all_users_key, 0, -1)]) );
            break;
        case 'open_restaurant':
            $redis->incr($message['restaurant_name']);
            Swoole\broadcast(json_encode(['type'=>'open_restaurant','value'=>$redis->get($message['restaurant_name'])]) );
            break;
        case 'close_restaurant':
            $redis->decr($message['restaurant_name']);
            Swoole\broadcast(json_encode(['type'=>'open_restaurant','value'=>$redis->get($message['restaurant_name'])]) );
            break;
        case 'new_viwer':   
            $redis->incr('total_viwers');
            Swoole\broadcast(json_encode(['type'=>'total_viwers','value'=>$redis->get('total_viwers')]));
        break;


    }
    


};
Swoole\websocket_hooks([
    'open' => 'addConnection',
    'close' => 'removeConnection',

]);


function  addConnection($request){
    
    echo  "new connection {$request->fd}\n";
};

function  removeConnection($fd){
    echo "disconnected {$fd}\n";
};



Swoole\websocket($echo,9501)->start();

