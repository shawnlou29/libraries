<?php 

    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;
    require '../src/vendor/autoload.php';

    use \Firebase\JWT\JWT;
    use \Firebase\JWT\Key;

    $app = new \Slim\App;

    //register (admin or user)
    $app->post('/users/register', function (Request $request, Response $response, array $args) {

        $data=json_decode($request->getBody());

        $email = $data->email;
        $uname = $data->username;
        $pass = $data->password;

        $servername="localhost" ;
        $password="";
        $username="root";
        $dbname="library";

        try {
            if (empty($email) || empty($uname) || empty($pass)) {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("Message" => "Fields cannot be empty.")))
                );
                return $response;
            }

            try{
                $conn = new PDO("mysql:host=$servername; dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $sql = "SELECT userid FROM users WHERE email = :email";
                $statement = $conn->prepare($sql);
                $statement->execute(['email' => $email]);
                $existing_email = $statement->fetch(PDO::FETCH_ASSOC);

                if($existing_email) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Invalid Email! Try another one.")))
                    );
                    return $response;
                }

                $sql = "INSERT INTO users (email, username, password, created_at) 
                        VALUES (:email, :username, :password, NOW())";
                $statement = $conn->prepare($sql);
                
                $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
                
                $statement->execute([
                    ':email' => $email,
                    ':username' => $uname,
                    ':password' => $hashedPassword,
                ]);

                $response->getBody()->write(json_encode(array("status"=>"success","data"=>null)));

            } catch(PDOException$e){
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => "Registration failed."))));
                error_log($e->getMessage());
            }
        } catch(Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }

        $conn=null;
        return $response;
    }); 

    //login account
    $app->post('/users/login', function (Request $request, Response $response, array $args) {
        $data = json_decode($request->getBody());
    
        $password = $data->password;
        $email = $data->email;
    
        $servername = "localhost";
        $dbpassword = ""; 
        $username = "root";
        $dbname = "library";
    
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $dbpassword);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT userid, username, password, access_level FROM users WHERE email = :email";
            $statement = $conn->prepare($sql);
            $statement->execute(['email' => $email]);
    
            $user = $statement->fetch(PDO::FETCH_ASSOC);
    
            if ($user && password_verify($password, $user['password'])) {
                
                $key = 'server_key';
                $expire = time();
                
                if ($user['access_level'] == "admin") {
                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 14400,
                        'data' => array(
                            'userid' => $user['userid'], 
                            "name" => $user['username'],
                            "access_level" => $user['access_level']
                        )
                    ];
    
                    $jwt = JWT::encode($payload, $key, 'HS256');

                    $updateSql = "UPDATE users SET token = :token WHERE userid = :userid";
                    $updateStatement = $conn->prepare($updateSql);
                    $updateStatement->execute(['token' => $jwt, 'userid' => $user['userid']]);
    
                    $response->getBody()->write(
                        json_encode(array("status" => "success", "token" => $jwt))
                    );

                } elseif (empty($user['access_level'])) {
                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 14400,
                        'data' => array(
                            'userid' => $user['userid'], 
                            "name" => $user['username'],
                            "access_level" => $user['access_level']
                        )
                    ];
    
                    $jwt = JWT::encode($payload, $key, 'HS256');

                    $tokenInsrt = "UPDATE users SET token = :token WHERE userid = :userid";
                    $updateStatement = $conn->prepare($tokenInsrt);
                    $updateStatement->execute(['token' => $jwt, 'userid' => $user['userid']]);
    
                    $response->getBody()->write(
                        json_encode(array("status" => "success", "token" => $jwt))
                    );

                } else {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Access Denied. Insufficient permissions.")))
                    );
                }
            } else {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("Message" => "Invalid email or password"))),
                );
            }
        } catch (Exception $e) {
            $response->getBody()->write(
                json_encode(array("status" => "fail", "data" => array("Message" => "Login failed.")))
            );
            error_log($e->getMessage());
        }
    
        $conn = null;
        return $response;
    });

    //Adding Books (Admin)
    $app->post("/books/add", function(Request $request, Response $response, array $args) {

        $data = json_decode($request->getBody());

        $author = $data->author;
        $title = $data->title;
        $genre = $data->genre;

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $key ='server_key';
        $data=json_decode($request->getBody());
        $jwt=$data->token;

        try{
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

            if (!isset($decoded->data->access_level) || $decoded->data->access_level !== 'admin') {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("Message" => "Access Denied. Only admins can add books.")))
                );
                return $response;
            }

            try {
                
                $conn = new PDO("mysql:host = $servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }

                $sql = "SELECT authorid FROM authors WHERE authorname = :author";
                $statement = $conn->prepare($sql);
                $statement->execute(['author' => $author]);
                $existing_author = $statement->fetch(PDO::FETCH_ASSOC);

                if(!$existing_author) {
                    $sql = "INSERT INTO authors (authorname) VALUES (:author)";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['author' => $author]);

                    $authorid = $conn->lastInsertId();
                } else {
                    $authorid = $existing_author['authorid'];
                }

                $numbers = rand(100, 999);
                $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

                $letterCode = $letters[rand(0, strlen($letters) - 1)] . $letters[rand(0, strlen($letters) - 1)];

                $bookCode = $numbers . $letterCode;

                $sql = "INSERT INTO books (title, genre, authorid, bookCode) VALUES (:title, :genre, :authorid, :bookCode)";
                $statement = $conn->prepare($sql);
                $statement->execute(['title' => $title, 'genre'=>$genre, 'authorid' => $authorid, 'bookCode' => $bookCode]);

                $bookid = $conn->lastInsertId();

                $insert_collection = "INSERT INTO books_collection (bookid, authorid) VALUES (:bookid, :authorid)";
                $stmnt = $conn->prepare($insert_collection);
                $stmnt->execute(['bookid' => $bookid, 'authorid' => $authorid]);

                $key = 'server_key';
                $expire = time();

                $payload = [
                    'iss' => 'http://library.org',
                    'aud' => 'http://library.com',
                    'iat' => $expire,
                    'exp' => $expire + 14400,
                    'data' => array(
                        'userid' => $userid, 
                        "name" => $username,
                        "access_level" => $access_level
                    )
                ];

                $new_jwt = JWT::encode($payload, $key, 'HS256');

                $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                $response->getBody()->write(
                    json_encode(array("status" => "success", "new_token" => $new_jwt))
                );

            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status"=>"fail", "data"=> array("Message"=>$e->getMessage()))));
            }
        } catch(Exception $e) {
            $response->getBody()->write(json_encode(array("status"=>"fail", "data"=>array("Message"=>$e->getMessage()))));
        }

        $conn = null;
        return $response;

    });

    //Update Books (Admin)
    $app->post("/books/update", function(Request $request, Response $response, array $args) {
        $data = json_decode($request->getBody());
    
        $bookCode = $data->bookCode;
        $author = $data->author !== '' ? $data->author : null;
        $title = $data->title !== '' ? $data->title : null;
        $genre = $data->genre !== '' ? $data->genre : null;
    
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";
    
        $key = 'server_key';
        $jwt = $data->token;
    
        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            if (!isset($decoded->data->access_level) || $decoded->data->access_level !== 'admin') {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("Message" => "Access Denied. Only admins can update books.")))
                );
                return $response;
            }

            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "SELECT * FROM books WHERE bookCode = :bookCode";
                $statement = $conn->prepare($sql);
                $statement->execute(['bookCode' => $bookCode]);
                $existing_book = $statement->fetch(PDO::FETCH_ASSOC);
    
                if (!$existing_book) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Invalid Book Code.")))
                    );
                    return $response;
                }
    
                if ($author !== null) {
                    $sql = "SELECT authorid FROM authors WHERE authorname = :author";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['author' => $author]);
                    $existing_author = $statement->fetch(PDO::FETCH_ASSOC);
    
                    if (!$existing_author) {
                        $sql = "INSERT INTO authors (authorname) VALUES (:author)";
                        $statement = $conn->prepare($sql);
                        $statement->execute(['author' => $author]);
                        $authorid = $conn->lastInsertId();
                    } else {
                        $authorid = $existing_author['authorid'];
                    }
                } else {
                    $authorid = $existing_book['authorid'];
                }
    
                $fields = [];
                $newValues = [];
    
                if ($title !== null) {
                    $fields[] = "title = :title";
                    $newValues[':title'] = $title;
                }
    
                if ($genre !== null) {
                    $fields[] = "genre = :genre";
                    $newValues[':genre'] = $genre;
                }
    
                if ($authorid !== null) {
                    $fields[] = "authorid = :authorid";
                    $newValues[':authorid'] = $authorid;
                }
    
                if (empty($fields)) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "No fields to update.")))
                    );
                    return $response;
                }
    
                $sql = "UPDATE books SET " . implode(", ", $fields) . " WHERE bookCode = :bookCode";
                $statement = $conn->prepare($sql);
    
                foreach ($newValues as $param => $value) {
                    $statement->bindValue($param, $value);
                }
                $statement->bindValue(':bookCode', $bookCode);
    
                $statement->execute();
    
                $key = 'server_key';
                $expire = time();

                $payload = [
                    'iss' => 'http://library.org',
                    'aud' => 'http://library.com',
                    'iat' => $expire,
                    'exp' => $expire + 3600,
                    'data' => array(
                        'userid' => $userid, 
                        "name" => $username,
                        "access_level" => $access_level
                    )
                ];

                $new_jwt = JWT::encode($payload, $key, 'HS256');

                $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                $response->getBody()->write(
                    json_encode(array("status" => "success", "new_token" => $new_jwt))
                );

            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });

    //Delete a book (Admin)
    $app->delete("/books/delete", function(Request $request, Response $response, array $args) {
        $data = json_decode($request->getBody());
    
        $bookCode = $data->bookCode;
    
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";
    
        $key = 'server_key';
        $jwt = $data->token;
    
        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            if (!isset($decoded->data->access_level) || $decoded->data->access_level !== 'admin') {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("Message" => "Access Denied. Only admins can update books.")))
                );
                return $response;
            }
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "SELECT * FROM books WHERE bookCode = :bookCode";
                $statement = $conn->prepare($sql);
                $statement->execute(['bookCode' => $bookCode]);
                $existing_book = $statement->fetch(PDO::FETCH_ASSOC);
    
                if ($existing_book) {
                    $sql = "DELETE FROM books WHERE bookCode = :bookCode";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['bookCode' => $bookCode]);

                    $key = 'server_key';
                    $expire = time();

                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600,
                        'data' => array(
                            'userid' => $userid, 
                            "name" => $username,
                            "access_level" => $access_level
                        )
                    ];

                    $new_jwt = JWT::encode($payload, $key, 'HS256');

                    $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                    $response->getBody()->write(
                        json_encode(array("status" => "success", "new_token" => $new_jwt))
                    );

                } else {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Invalid Book Code.")))
                    );
                    return $response;
                }
            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });

    //Display all Books from the Books Collection
    $app->get("/books/displayAll", function (Request $request, Response $response, array $args) {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $key ='server_key';
        $data=json_decode($request->getBody());
        $jwt=$data->token;

        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "
                    SELECT 
                        books.bookid, 
                        books.title, 
                        books.genre, 
                        books.bookCode, 
                        authors.authorid, 
                        authors.authorname
                    FROM 
                        books_collection
                    JOIN 
                        books ON books_collection.bookid = books.bookid
                    JOIN 
                        authors ON books_collection.authorid = authors.authorid
                ";

                $statement = $conn->query($sql);
                $booksCount = $statement->rowCount();
                $displayBooks = $statement->fetchAll(PDO::FETCH_ASSOC);

                if ($booksCount > 0) {
                    $key = 'server_key';
                    $expire = time();

                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600,
                        'data' => array(
                            'userid' => $userid, 
                            "name" => $username,
                            "access_level" => $access_level
                        )
                    ];

                    $new_jwt = JWT::encode($payload, $key, 'HS256');

                    $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                    $response->getBody()->write(
                        json_encode(array("status" => "success", "new_token" => $new_jwt, "data" => $displayBooks))
                    );
                } else {
                    $response->getBody()->write(json_encode(array("status" => "success", "data" => "No books found.")));
                }

            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("title" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });

    //Display Books from author of the Books Collection
    $app->get("/books/displayauthorsbooks", function (Request $request, Response $response, array $args) {
        $data=json_decode($request->getBody());
        
        $authorname = $data->authorname;

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $key ='server_key';
        $jwt=$data->token;

        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "
                    SELECT 
                        books.bookid, 
                        books.title, 
                        books.genre,
                        books.bookCode,  
                        authors.authorid, 
                        authors.authorname
                    FROM 
                        books_collection
                    JOIN 
                        books ON books_collection.bookid = books.bookid
                    JOIN 
                        authors ON books_collection.authorid = authors.authorid
                    WHERE
                        authors.authorname = :authorname
                ";

                $statement = $conn->prepare($sql);
                $statement->execute(['authorname'=>$authorname]);
                $booksCount = $statement->rowCount();

                if ($booksCount > 0) {
                    $displayBooks = $statement->fetchAll(PDO::FETCH_ASSOC);

                    $key = 'server_key';
                    $expire = time();

                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600,
                        'data' => array(
                            'userid' => $userid, 
                            "name" => $username,
                            "access_level" => $access_level
                        )
                    ];

                    $new_jwt = JWT::encode($payload, $key, 'HS256');

                    $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                    $response->getBody()->write(
                        json_encode(array("status" => "success", "new_token" => $new_jwt, "data" => $displayBooks))
                    );
                } else {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "No such author exists.")))
                    );
                }

            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });

    //Display Books from book title of the Books Collection
    $app->get("/books/displaytitlebooks", function (Request $request, Response $response, array $args) {
        $data=json_decode($request->getBody());
        
        $booktitle = $data->booktitle;

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $key ='server_key';
        $jwt=$data->token;

        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "
                    SELECT 
                        books.bookid, 
                        books.title, 
                        books.genre, 
                        books.bookCode,
                        authors.authorid, 
                        authors.authorname
                    FROM 
                        books_collection
                    JOIN 
                        books ON books_collection.bookid = books.bookid
                    JOIN 
                        authors ON books_collection.authorid = authors.authorid
                    WHERE
                        books.title = :booktitle
                ";

                $statement = $conn->prepare($sql);
                $statement->execute(['booktitle'=>$booktitle]);
                $booksCount = $statement->rowCount();

                if ($booksCount > 0) {
                    $displayBooks = $statement->fetchAll(PDO::FETCH_ASSOC);

                    $key = 'server_key';
                    $expire = time();

                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600,
                        'data' => array(
                            'userid' => $userid, 
                            "name" => $username,
                            "access_level" => $access_level
                        )
                    ];

                    $new_jwt = JWT::encode($payload, $key, 'HS256');

                    $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                    $response->getBody()->write(
                        json_encode(array("status" => "success", "new_token" => $new_jwt, "data" => $displayBooks))
                    );

                } else {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "No such book title exists.")))
                    );
                }

            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });

    //Display Books from book genre of the Books Collection
    $app->get("/books/displaygenrebooks", function (Request $request, Response $response, array $args) {
        $data=json_decode($request->getBody());
        
        $bookgenre = $data->bookgenre;

        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $key ='server_key';
        $jwt=$data->token;

        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "
                    SELECT 
                        books.bookid, 
                        books.title, 
                        books.genre, 
                        books.bookCode,
                        authors.authorid, 
                        authors.authorname
                    FROM 
                        books_collection
                    JOIN 
                        books ON books_collection.bookid = books.bookid
                    JOIN 
                        authors ON books_collection.authorid = authors.authorid
                    WHERE
                        books.genre = :bookgenre
                ";

                $statement = $conn->prepare($sql);
                $statement->execute(['bookgenre'=>$bookgenre]);
                $booksCount = $statement->rowCount();

                if ($booksCount > 0) {
                    $displayBooks = $statement->fetchAll(PDO::FETCH_ASSOC);

                    $key = 'server_key';
                    $expire = time();

                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600,
                        'data' => array(
                            'userid' => $userid, 
                            "name" => $username,
                            "access_level" => $access_level
                        )
                    ];

                    $new_jwt = JWT::encode($payload, $key, 'HS256');

                    $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                    $response->getBody()->write(
                        json_encode(array("status" => "success", "new_token" => $new_jwt, "data" => $displayBooks))
                    );
                } else {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "No such book genre exists.")))
                    );
                }

            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });
    
    //Add Author (Admin)
$app->post("/authors/add", function (Request $request, Response $response, array $args) {
    $data = json_decode($request->getBody());

    $authorname = $data->authorname;

    $servername = "localhost";
    $password = "";
    $username = "root";
    $dbname = "library";

    $key = 'server_key'; // Updated key
    $jwt = $data->token;

    try {
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        if (!isset($decoded->data->access_level) || $decoded->data->access_level !== 'admin') {
            $response->getBody()->write(
                json_encode(array("status" => "fail", "data" => array("Message" => "Access Denied. Only admins can add authors.")))
            );
            return $response;
        }

        try {
            $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password); // Updated connection string
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $userid = $decoded->data->userid;
            $access_level = $decoded->data->access_level;

            $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
            $statement = $conn->prepare($sql);
            $statement->execute(['userid' => $userid]);
            $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

            if ($userInfo['token'] !== $jwt) {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                );
                return $response;
            }

            $sql = "SELECT authorid FROM authors WHERE authorname = :authorname";
            $statement = $conn->prepare($sql);
            $statement->execute(['authorname' => $authorname]);
            $existing_author = $statement->fetch(PDO::FETCH_ASSOC);

            if ($existing_author) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => "Author already exists."))));
                return $response;
            }

            $sql = "INSERT INTO authors (authorname) VALUES (:authorname)";
            $statement = $conn->prepare($sql);

            $statement->execute([":authorname" => $authorname]);

            $expire = time();

            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $expire,
                'exp' => $expire + 3600,
                'data' => array(
                    'userid' => $userid, 
                    "name" => $username,
                    "access_level" => $access_level
                )
            ];

            $new_jwt = JWT::encode($payload, $key, 'HS256');

            $sql = "UPDATE users SET token = :token WHERE userid = :userid";
            $statement = $conn->prepare($sql);
            $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

            $response->getBody()->write(
                json_encode(array("status" => "success", "new_token" => $new_jwt))
            );

        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }

    } catch (Exception $e) {
        $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
    }

    $conn = null;
    return $response;
});


    //Update Author (Admin)
    $app->post("/authors/update", function(Request $request, Response $response, array $args) {
        $data = json_decode($request->getBody());
    
        $authorid = $data->authorid !== '' ? $data->authorid : null;
        $authorname = $data->authorname !== '' ? $data->authorname : null;
    
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";
    
        $key = 'server_key';
        $jwt = $data->token;
    
        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            if (!isset($decoded->data->access_level) || $decoded->data->access_level !== 'admin') {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("title" => "Access Denied. Only admins can update books.")))
                );
                return $response;
            }
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "SELECT * FROM authors WHERE authorid = :authorid";
                $statement = $conn->prepare($sql);
                $statement->execute(['authorid' => $authorid]);
                $existing_authorid = $statement->fetch(PDO::FETCH_ASSOC);
    
                if (!$existing_authorid) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Invalid Author ID.")))
                    );
                    return $response;
                }

                $fields = [];
                $newValue = [];

                if ($authorname !== null) {
                    $fields[] = "authorname = :authorname";
                    $newValue[':authorname'] = $authorname;
                }

                if (empty($fields)) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "No fields to update.")))
                    );
                    return $response;
                }
    
                $sql = "UPDATE authors SET " . implode(", ", $fields) . " WHERE authorid = :authorid";
                $statement = $conn->prepare($sql);
    
                foreach ($newValue as $param => $value) {
                    $statement->bindValue($param, $value);
                }
                $statement->bindValue(':authorid', $authorid);
    
                $statement->execute();
    
                $key = 'server_key';
                $expire = time();

                $payload = [
                    'iss' => 'http://library.org',
                    'aud' => 'http://library.com',
                    'iat' => $expire,
                    'exp' => $expire + 3600,
                    'data' => array(
                        'userid' => $userid, 
                        "name" => $username,
                        "access_level" => $access_level
                    )
                ];

                $new_jwt = JWT::encode($payload, $key, 'HS256');

                $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                $response->getBody()->write(
                    json_encode(array("status" => "success", "new_token" => $new_jwt))
                );

            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });

    //Deleting an Author (Admin)
    $app->delete("/authors/delete", function(Request $request, Response $response, array $args) {
        $data = json_decode($request->getBody());
    
        $authorid = $data->authorid;
    
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";
    
        $key = 'server_key';
        $jwt = $data->token;
    
        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            if (!isset($decoded->data->access_level) || $decoded->data->access_level !== 'admin') {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("Message" => "Access Denied. Only admins can update books.")))
                );
                return $response;
            }
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "SELECT * FROM authors WHERE authorid = :authorid";
                $statement = $conn->prepare($sql);
                $statement->execute(['authorid' => $authorid]);
                $existing_book = $statement->fetch(PDO::FETCH_ASSOC);
    
                if ($existing_book) {
                    $sql = "DELETE FROM authors WHERE authorid = :authorid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['authorid' => $authorid]);

                    $key = 'server_key';
                    $expire = time();

                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600,
                        'data' => array(
                            'userid' => $userid, 
                            "name" => $username,
                            "access_level" => $access_level
                        )
                    ];

                    $new_jwt = JWT::encode($payload, $key, 'HS256');

                    $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                    $response->getBody()->write(
                        json_encode(array("status" => "success", "new_token" => $new_jwt))
                    );

                } else {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Invalid Author ID.")))
                    );
                    return $response;
                }
            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });

    //Display all Authors 
    $app->get("/authors/display", function (Request $request, Response $response, array $args) {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";

        $key ='server_key';
        $data=json_decode($request->getBody());
        $jwt=$data->token;

        try {
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $userid = $decoded->data->userid;
                $access_level = $decoded->data->access_level;

                $sql = "SELECT username, password, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

                if ($userInfo['token'] !== $jwt) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Token is invalid or outdated.")))
                    );
                    return $response;
                }
    
                $sql = "SELECT * FROM authors";
                $statement = $conn->query($sql);
                $authorsCount = $statement->rowCount();
                $displayAuthors = $statement->fetchAll(PDO::FETCH_ASSOC);

                if ($authorsCount > 0) {
                    $key = 'server_key';
                    $expire = time();

                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600,
                        'data' => array(
                            'userid' => $userid, 
                            "name" => $username,
                            "access_level" => $access_level
                        )
                    ];

                    $new_jwt = JWT::encode($payload, $key, 'HS256');

                    $sql = "UPDATE users SET token = :token  WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                    $response->getBody()->write(
                        json_encode(array("status" => "success", "new_token" => $new_jwt, "data" => $displayAuthors))
                    );
                } else {
                    $response->getBody()->write(json_encode(array("status" => "fail", "Message" => "No authors found.")));
                }

            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response;
    });

    $app->run();

?>