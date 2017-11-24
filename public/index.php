<?php
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    header('Access-Control-Allow-Origin: *');
    /*header('Access-Control-Allow-Methods: *');
    header('Content-Type: application/json');
    */

    require '../vendor/autoload.php';

    require '../config/config.php';
    require '../src/functions/init.php';
    require '../src/classes/init.php';
    
    $app = new \Slim\App;
    $app->get('/test', function (Request $request, Response $response) {
        try{
            $res = Friends::invite('qRSBY1Y6xYojnoyjvXq8aevu5qhLqTiRLasJcrQJpf2MEB69ywhILMdQduCDoUu95XThoJ1NZ3ADFHMdd4WZ', 2);
            echo json_encode($res);
            
            //print_r(Config::get('response_format/response'));
            /*$ret = Config::response(Config::response(), 'response/state', 'true');
            print_r(Config::response($ret, 'response/message', 'Ok... test works'));*/
        }catch(Exception $exc){
            echo $exc->getMessage();
        }
    });

    $app->get('/profile', function (Request $request, Response $response) {
        $input = ft_escape_array($request->getParsedBody());
        //$input['username'] = 'mkgosisejo';
        //$input['session'] = '0i2ljuJrrJPRSOeo1mJNQzvZg35scXPdgRzAli1M1QEUFTiHf1u6BZ5S3akf89to02YmlZ9nQNhwHAdWCH3d';

        if (isset($input['username'])){
            $db = new Database();

            //run raw query "WHERE blocked_user.id != id..."
            if (($data = $db->select('tbl_users', array('username', '=', $input['username']), null, true))){
                if ($data->rowCount){
                    $data = $data->rows[0];
                    //$images_data = ''; //#Remove...!

                    //Appending users Photos on user's info...
                    //echo $query = "SELECT * FROM tbl_user_images WHERE user_id = ".$data['id'].";";
                    if (($images = $db->select('tbl_user_images', array('user_id', '=', $data['id']), null, true))){
                        if ($images->rowCount > 0){
                            $data['images'] = $images->rows;
                            
                            foreach ($images->rows as $image){
                                $data['img'.$image['code']] = $image;
                            }
                        }
                    }

                    //Appending Friendship info of logged user with viewed user if not him/her self (logged user)...
                    if (isset($input['session'])){
                        if ($logged_user = User::info(array('token' => $input['session']))){
                            $logged_user = (object)$logged_user['data'];
                            $viewed_user = (object)$data;

                            if ($logged_user->id !== $viewed_user->id){
                                $query = "SELECT * FROM tbl_user_connections WHERE (user_id_from = $logged_user->id AND user_id_to = $viewed_user->id) OR (user_id_from = $viewed_user->id AND user_id_to = $logged_user->id);";

                                if (($conn_data = Database::rawQuery($query, true))){
                                    $conn_data = (object)$conn_data;
                                    if ($conn_data->rowCount > 0){
                                        $conn_data = (object)$conn_data->rows[0];
                                        $relationship['status'] =  $conn_data->status;
                                        $relationship['user_id_from'] = $conn_data->user_id_from;
                                        $relationship['user_id_to'] = $conn_data->user_id_to;
                                        
                                        $data['relationship'] = $relationship;
                                    }
                                }
                            }
                        }
                    }

                    $res = Config::response(Config::response(), 'response/state', 'true');
                    $res = Config::response($res, 'data', $data);
                    echo json_encode($res);
                    return ;
                }
                echo json_encode(Config::response(Config::response(), 'response/message', $input['username'] .' was not found.'));
                return ;
            }
        }
        echo '{}';
    });

    $app->get('/suggestions', function (Request $request, Response $response) {
        try{
            $input = ft_escape_array($request->getParsedBody());

            print_r($input);
            //$res = friends::suggestions(2);
            //echo json_encode($res);
        }catch(Exception $exc){
            echo $exc->getMessage();
        }
    });

    $app->get('/info', function (Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());

        if (isset($input['session'])){
            $res = User::info(array('token' => $input['session']));
            echo json_encode($res);
        }else
            echo '{}';
    });

    $app->post('/update-profile', function (Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());

        if (isset($input['session']) && isset($input['fname']) && isset($input['lname']) && isset($input['gender']) &&
                isset($input['dob']) && isset($input['sexual_preference']) && isset($input['bio'])){

            $res = User::update_profile($input['session'], $input['fname'], $input['lname'], $input['gender'], $input['dob'], $input['sexual_preference'], $input['bio']);
            echo json_encode($res);
        }else
            echo '{}';
    });

    $app->post('/login', function (Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());

        if (!isset($input['isSession']))
            echo '{}';
        if ($input['isSession'] == 1){
            if (isset($input['session'])){
                $res = User::info(array('token' => $input['session']));
                echo json_encode($res);
            }
        }
        else {
            if (isset($input['login']) && isset($input['password'])){
                $res = User::login($input['login'], $input['password']);
                echo json_encode($res);
            }
        }
    });

    $app->post('/profile-images', function (Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());
        
        if (isset($input['session']) && isset($input['image']) && isset($input['code'])){
            $res = User::upload_profile($input['session'], $input['image'], $input['code']);
            echo json_encode($res);
        }else
            echo '{}';
    });

    $app->post('/register', function (Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());

        if (isset($input['fname']) && isset($input['lname']) && isset($input['username']) && isset($input['email']) && isset($input['password'])){
            $res = User::register($input['fname'], $input['lname'], $input['username'], $input['email'], $input['password']);
            echo json_encode($res);
        }else
            echo '{}';
    });

    $app->get('/logut', function (Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());

        if (isset($input['session'])){
            $res = User::logout($input['session']);
            echo json_encode($res);
        }else
            echo '{}';
    });

    $app->post('/confirm-registration', function(Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());
        
        if (isset($input['token'])){
            $res = User::confirm_registration($input['token']);
            //$ret = Config::response(Config::response(), 'response/state', 'true');
           // $res = Config::response($ret, 'response/message', 'Ok... test works');
            echo json_encode($res);
        }else
            echo '{}';
    });

    $app->post('/search', function(Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());
        
        if (isset($input['search_value'])){
            $res = Friends::search($input['search_value']);
            echo json_encode($res);
        }else
            echo '{}';
    });

    $app->post('/invite', function(Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());
        new Database();
        //$input['session'] = 'OeWPVBOI1SfqgEp9UYQjOg4C1hBKeBQ2QMSMoHvqAKRRpg0jeQC26HF8YgSdSIgJv9vUQ0krLciasiuG97Jg';
        //$input['username'] = 'pkaygo';

        if (isset($input['session']) && isset($input['username'])){
            $where = array(
                'username', '=', $input['username']
            );
            if (($data = Database::select('tbl_users', $where, null, true))){
                if ($data->rowCount > 0){
                    $user_to = (object)$data->rows[0];
                    $res = Friends::invite($input['session'], $user_to->id);
                    echo json_encode($res);
                    return ;
                }
            }
            echo json_encode(Config::response(Config::response(), 'response/message', 'Selected user was not found.'));
        }else
            echo '{}';
    });

    $app->post('/accept-invite', function(Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());
        new Database();
        //$input['session'] = '0i2ljuJrrJPRSOeo1mJNQzvZg35scXPdgRzAli1M1QEUFTiHf1u6BZ5S3akf89to02YmlZ9nQNhwHAdWCH3d';
        //$input['username'] = 'mkgosisejo';

        if (isset($input['session']) && isset($input['username'])){
            $where = array(
                'username', '=', $input['username']
            );
            if (($data = Database::select('tbl_users', $where, null, true))){
                if ($data->rowCount > 0){
                    $user_to = (object)$data->rows[0];
                    $res = Friends::accept_invite($input['session'], $user_to->id);
                    echo json_encode($res);
                    return ;
                }
            }
            echo json_encode(Config::response(Config::response(), 'response/message', 'Selected user was not found.'));
        }else
            echo '{}';
    });

    $app->get('/friend-list', function(Request $request, Response $response){
        $input = ft_escape_array($request->getParsedBody());
        //$input['session'] = '0i2ljuJrrJPRSOeo1mJNQzvZg35scXPdgRzAli1M1QEUFTiHf1u6BZ5S3akf89to02YmlZ9nQNhwHAdWCH3d';

        if (isset($input['session'])){
            $res = Friends::_list($input['session']);
            echo json_encode($res);
        }else
            echo '{}';
    });

    $app->run();
?>