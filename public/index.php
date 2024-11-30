<?php 

    use Psr\Http\Message\ServerRequestInterface as Request;
    use Psr\Http\Message\ResponseInterface as Response;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    require '../src/vendor/autoload.php';

    $app = new \Slim\App;

    header("Access-Control-Allow-Origin: http://127.0.0.1:5500");  // Corrected, without trailing slash
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, token");
    header("Access-Control-Allow-Credentials: true");

    //register (admin or user)
    $app->post('/users/register', function (Request $request, Response $response, array $args) {
        $data = json_decode($request->getBody());
    
        $email = $data->email ?? null;
        $uname = $data->username ?? null;
        $pass = $data->password ?? null;
        $admin = $data->access_level ?? null;
    
        $servername = "localhost";
        $password = "";
        $username = "root";
        $dbname = "library";
    
        try {
            if (empty($email) || empty($uname) || empty($pass) || empty($admin)) {
                $response->getBody()->write(
                    json_encode(array("status" => "fail", "data" => array("Message" => "Fields cannot be empty.")))
                );
                return $response->withHeader('Content-Type', 'application/json');
            }
    
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
                // Check if email already exists
                $sql = "SELECT userid FROM users WHERE email = :email";
                $statement = $conn->prepare($sql);
                $statement->execute(['email' => $email]);
                $existing_email = $statement->fetch(PDO::FETCH_ASSOC);
    
                if ($existing_email) {
                    $response->getBody()->write(
                        json_encode(array("status" => "fail", "data" => array("Message" => "Invalid Email! Try another one.")))
                    );
                    return $response->withHeader('Content-Type', 'application/json');
                }
    
                // Insert new user into the database, including access_level
                $sql = "INSERT INTO users (email, username, password, access_level, created_at) 
                        VALUES (:email, :username, :password, :access_level, NOW())";
                $statement = $conn->prepare($sql);
    
                $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);
    
                $statement->execute([
                    ':email' => $email,
                    ':username' => $uname,
                    ':password' => $hashedPassword,
                    ':access_level' => $admin,
                ]);
    
                $response->getBody()->write(json_encode(array("status" => "success", "data" => null)));
    
            } catch (PDOException $e) {
                $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => "Registration failed."))));
                error_log($e->getMessage());
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array("status" => "fail", "data" => array("Message" => $e->getMessage()))));
        }
    
        $conn = null;
        return $response->withHeader('Content-Type', 'application/json');
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

        // Retrieve the JWT from the Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $response->withStatus(400)->write(
                json_encode(['status' => 'fail', 'data' => ['Message' => 'Token is missing or invalid']])
            );
        }
    
        $jwt = $matches[1];  // Extract token
    
        try {
            // Decode the JWT
            $decoded = JWT::decode($jwt, new Key('server_key', 'HS256'));
    
            // Check if the user has admin access level
            if (!isset($decoded->data->access_level) || $decoded->data->access_level !== 'admin') {
                return $response->withStatus(403)->write(
                    json_encode(['status' => 'fail', 'data' => ['Message' => 'Access Denied. Only admins can add books.']])
                );
            }
    
            // Retrieve data from the request body
            $data = $request->getParsedBody();
            $authorid = $data['authorid'] ?? null;
            $title = $data['title'] ?? null;
            $genre = $data['genre'] ?? null;
    
            // Validate input data
            if (!$authorid || !$title || !$genre) {
                return $response->withStatus(400)->write(
                    json_encode(['status' => 'fail', 'data' => ['Message' => 'Missing required fields: authorid, title, or genre.']])
                );
            }
    
            // Database connection
            $conn = new PDO("mysql:host=localhost;dbname=library", 'root', '');
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
            // Fetch author name based on authorid
            $sql = "SELECT authorname FROM authors WHERE authorid = :authorid";
            $statement = $conn->prepare($sql);
            $statement->execute(['authorid' => $authorid]);
            $author = $statement->fetch(PDO::FETCH_ASSOC);
    
            // Validate if author exists
            if (!$author) {
                return $response->withStatus(400)->write(
                    json_encode(['status' => 'fail', 'data' => ['Message' => 'Invalid author ID. Author not found.']])
                );
            }
    
            $authorname = $author['authorname'];
    
            // Insert the new book
            $sql = "INSERT INTO books (title, genre, authorid) VALUES (:title, :genre, :authorid)";
            $statement = $conn->prepare($sql);
            $statement->execute([
                'title' => $title,
                'genre' => $genre,
                'authorid' => $authorid
            ]);
    
            $bookid = $conn->lastInsertId(); // Get the ID of the newly inserted book
    
            // Generate a new JWT token with updated details (if necessary)
            $expire = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $expire,
                'exp' => $expire + 14400, // Token expiration time
                'data' => [
                    'userid' => $decoded->data->userid,
                    'name' => $decoded->data->name,
                    'access_level' => $decoded->data->access_level
                ]
            ];
    
            $new_jwt = JWT::encode($payload, 'server_key', 'HS256');
    
            // Update the token in the users table
            $sql = "UPDATE users SET token = :token WHERE userid = :userid";
            $statement = $conn->prepare($sql);
            $statement->execute(['token' => $new_jwt, 'userid' => $decoded->data->userid]);
    
            // Return success response with the new token and book ID
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'new_token' => $new_jwt,
                'bookid' => $bookid,
                'authorname' => $authorname // Include author name in the response
            ]));
    
        } catch (Exception $e) {
            // Handle any errors
            $response->getBody()->write(json_encode(['status' => 'fail', 'data' => ['Message' => $e->getMessage()]]));
            return $response->withStatus(500); // Internal Server Error
        }
    
        return $response->withHeader('Content-Type', 'application/json');
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
    $app->delete("/books/delete/{id}", function(Request $request, Response $response, array $args) {

        // Retrieve the JWT from the Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $response->withStatus(400)->write(
                json_encode(['status' => 'fail', 'data' => ['Message' => 'Token is missing or invalid']])
            );
        }
    
        $jwt = $matches[1];  // Extract token
    
        try {
            // Decode the JWT
            $decoded = JWT::decode($jwt, new Key('server_key', 'HS256'));
    
            // Check if the user has admin access level
            if (!isset($decoded->data->access_level) || $decoded->data->access_level !== 'admin') {
                return $response->withStatus(403)->write(
                    json_encode(['status' => 'fail', 'data' => ['Message' => 'Access Denied. Only admins can delete books.']])
                );
            }
    
            // Retrieve the book ID from the URL
            $bookId = $args['id'];
    
            // Database connection
            $conn = new PDO("mysql:host=localhost;dbname=library", 'root', '');
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
            // Check if the book exists
            $sql = "SELECT * FROM books WHERE bookid = :bookid";
            $statement = $conn->prepare($sql);
            $statement->execute(['bookid' => $bookId]);
            $book = $statement->fetch(PDO::FETCH_ASSOC);
    
            if (!$book) {
                return $response->withStatus(400)->write(
                    json_encode(['status' => 'fail', 'data' => ['Message' => 'Book not found.']])
                );
            }
    
            // Delete the book
            $sql = "DELETE FROM books WHERE bookid = :bookid";
            $statement = $conn->prepare($sql);
            $statement->execute(['bookid' => $bookId]);
    
            // Generate a new JWT token with updated details (if necessary)
            $expire = time();
            $payload = [
                'iss' => 'http://library.org',
                'aud' => 'http://library.com',
                'iat' => $expire,
                'exp' => $expire + 14400, // Token expiration time
                'data' => [
                    'userid' => $decoded->data->userid,
                    'name' => $decoded->data->name,
                    'access_level' => $decoded->data->access_level
                ]
            ];
    
            $new_jwt = JWT::encode($payload, 'server_key', 'HS256');
    
            // Optionally, update the token in the database (if needed)
            $sql = "UPDATE users SET token = :token WHERE userid = :userid";
            $statement = $conn->prepare($sql);
            $statement->execute(['token' => $new_jwt, 'userid' => $decoded->data->userid]);
    
            // Return success response with the new token and deleted book details
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'new_token' => $new_jwt,
                'bookid' => $bookId,
                'message' => 'Book deleted successfully.'
            ]));
    
        } catch (Exception $e) {
            // Handle any errors, including token errors
            $response->getBody()->write(json_encode(['status' => 'fail', 'data' => ['Message' => $e->getMessage()]]));
            return $response->withStatus(500); // Internal Server Error
        }
    
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    
    
    // Display all Books from the Books Collection
    $app->get("/books/displayAll", function (Request $request, Response $response, array $args) {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";
        $key = 'server_key';
    
        // Retrieve the token from the Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
    
        // Check if the token is provided in the correct format
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $response->withStatus(400)->write(
                json_encode(['status' => 'fail', 'message' => 'Token is missing or invalid'])
            );
        }
    
        $jwt = $matches[1]; // Extract the token from the header
    
        try {
            // Decode the JWT
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            // Extract user details from the decoded token
            $userid = $decoded->data->userid;
            $access_level = $decoded->data->access_level;
    
            try {
                // Connect to the database
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
                // Verify the token in the database
                $sql = "SELECT username, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);
    
                if ($userInfo['token'] !== $jwt) {
                    return $response->withStatus(401)->write(
                        json_encode(['status' => 'fail', 'message' => 'Token is invalid or outdated'])
                    );
                }
    
                // Fetch all books along with their authors
                $sql = "SELECT b.bookid, b.title, b.genre, a.authorname 
                        FROM books b 
                        JOIN authors a ON b.authorid = a.authorid 
                        ORDER BY b.bookid ASC";
                $statement = $conn->query($sql);
                $booksCount = $statement->rowCount();
                $displayBooks = $statement->fetchAll(PDO::FETCH_ASSOC);
    
                if ($booksCount > 0) {
                    // Generate a new token
                    $expire = time();
                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600, // Token expires in 1 hour
                        'data' => [
                            'userid' => $userid,
                            'name' => $userInfo['username'],
                            'access_level' => $access_level
                        ]
                    ];
                    $new_jwt = JWT::encode($payload, $key, 'HS256');
    
                    // Update the new token in the database
                    $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);
    
                    // Send the books and the new token as the response
                    $response->getBody()->write(
                        json_encode(['status' => 'success', 'new_token' => $new_jwt, 'data' => $displayBooks])
                    );
                } else {
                    $response->getBody()->write(
                        json_encode(['status' => 'fail', 'message' => 'No books found'])
                    );
                }
            } catch (PDOException $e) {
                $response->getBody()->write(
                    json_encode(['status' => 'fail', 'message' => $e->getMessage()])
                );
            }
        } catch (Exception $e) {
            $response->getBody()->write(
                json_encode(['status' => 'fail', 'message' => 'Invalid token: ' . $e->getMessage()])
            );
        }
    
        // Close the connection
        $conn = null;
        return $response->withHeader('Content-Type', 'application/json');
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


$app->get("/authors/display", function (Request $request, Response $response, array $args) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "library";
    $key = 'server_key';

    // Retrieve the token from the Authorization header
    $authHeader = $request->getHeaderLine('Authorization');

    // Check if the token is provided in the correct format
    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return $response->withStatus(400)->write(
            json_encode(['status' => 'fail', 'message' => 'Token is missing or invalid'])
        );
    }

    $jwt = $matches[1]; // Extract the token from the header

    try {
        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));

        // Extract user details from the decoded token
        $userid = $decoded->data->userid;
        $access_level = $decoded->data->access_level;

        try {
            // Connect to the database
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verify the token in the database
            $sql = "SELECT username, token FROM users WHERE userid = :userid";
            $statement = $conn->prepare($sql);
            $statement->execute(['userid' => $userid]);
            $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

            if ($userInfo['token'] !== $jwt) {
                return $response->withStatus(401)->write(
                    json_encode(['status' => 'fail', 'message' => 'Token is invalid or outdated'])
                );
            }

            // Fetch all authors
            $sql = "SELECT * FROM authors";
            $statement = $conn->query($sql);
            $authorsCount = $statement->rowCount();
            $displayAuthors = $statement->fetchAll(PDO::FETCH_ASSOC);

            if ($authorsCount > 0) {
                // Generate a new token
                $expire = time();
                $payload = [
                    'iss' => 'http://library.org',
                    'aud' => 'http://library.com',
                    'iat' => $expire,
                    'exp' => $expire + 3600, // Token expires in 1 hour
                    'data' => [
                        'userid' => $userid,
                        'name' => $userInfo['username'],
                        'access_level' => $access_level
                    ]
                ];
                $new_jwt = JWT::encode($payload, $key, 'HS256');

                // Update the new token in the database
                $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['token' => $new_jwt, 'userid' => $userid]);

                // Send the authors and the new token as the response
                $response->getBody()->write(
                    json_encode(['status' => 'success', 'new_token' => $new_jwt, 'data' => $displayAuthors])
                );
            } else {
                $response->getBody()->write(
                    json_encode(['status' => 'fail', 'message' => 'No authors found'])
                );
            }
        } catch (PDOException $e) {
            $response->getBody()->write(
                json_encode(['status' => 'fail', 'message' => $e->getMessage()])
            );
        }
    } catch (Exception $e) {
        $response->getBody()->write(
            json_encode(['status' => 'fail', 'message' => 'Invalid token: ' . $e->getMessage()])
        );
    }

    // Close the connection
    $conn = null;
    return $response->withHeader('Content-Type', 'application/json');
});

    // Find author by id
    $app->get("/authors/{id}", function (Request $request, Response $response, array $args) {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "library";
        $key = 'server_key';
    
        // Retrieve the token from the Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
    
        // Check if the token is provided in the correct format
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $response->withStatus(400)->write(
                json_encode(['status' => 'fail', 'message' => 'Token is missing or invalid'])
            );
        }
    
        $jwt = $matches[1]; // Extract the token from the header
    
        try {
            // Decode the JWT
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
    
            // Extract user details from the decoded token
            $userid = $decoded->data->userid;
            $access_level = $decoded->data->access_level;
    
            try {
                // Connect to the database
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
                // Verify the token in the database
                $sql = "SELECT username, token FROM users WHERE userid = :userid";
                $statement = $conn->prepare($sql);
                $statement->execute(['userid' => $userid]);
                $userInfo = $statement->fetch(PDO::FETCH_ASSOC);
    
                if ($userInfo['token'] !== $jwt) {
                    return $response->withStatus(401)->write(
                        json_encode(['status' => 'fail', 'message' => 'Token is invalid or outdated'])
                    );
                }
    
                // Get the author ID from the route parameters
                $authorId = $args['id'];
    
                // Fetch the author by ID
                $sql = "SELECT * FROM authors WHERE authorid = :authorid";
                $statement = $conn->prepare($sql);
                $statement->execute(['authorid' => $authorId]);
                $author = $statement->fetch(PDO::FETCH_ASSOC);
    
                if ($author) {
                    // Generate a new token
                    $expire = time();
                    $payload = [
                        'iss' => 'http://library.org',
                        'aud' => 'http://library.com',
                        'iat' => $expire,
                        'exp' => $expire + 3600, // Token expires in 1 hour
                        'data' => [
                            'userid' => $userid,
                            'name' => $userInfo['username'],
                            'access_level' => $access_level
                        ]
                    ];
                    $new_jwt = JWT::encode($payload, $key, 'HS256');
    
                    // Update the new token in the database
                    $sql = "UPDATE users SET token = :token WHERE userid = :userid";
                    $statement = $conn->prepare($sql);
                    $statement->execute(['token' => $new_jwt, 'userid' => $userid]);
    
                    // Send the author details and the new token as the response
                    $response->getBody()->write(
                        json_encode(['status' => 'success', 'new_token' => $new_jwt, 'data' => $author])
                    );
                } else {
                    $response->getBody()->write(
                        json_encode(['status' => 'fail', 'message' => 'Author not found'])
                    );
                }
            } catch (PDOException $e) {
                $response->getBody()->write(
                    json_encode(['status' => 'fail', 'message' => $e->getMessage()])
                );
            }
        } catch (Exception $e) {
            $response->getBody()->write(
                json_encode(['status' => 'fail', 'message' => 'Invalid token: ' . $e->getMessage()])
            );
        }
    
        // Close the connection
        $conn = null;
        return $response->withHeader('Content-Type', 'application/json');
    });
    

    function getAuthorNameById($id, $servername, $username, $password, $dbname) {
        try {
            // Connect to the database
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
            // Fetch the author name by ID
            $sql = "SELECT authorname FROM authors WHERE authorid = :authorid";
            $statement = $conn->prepare($sql);
            $statement->execute(['authorid' => $id]);
            $author = $statement->fetch(PDO::FETCH_ASSOC);
    
            // Return the author name if found, otherwise return null
            return $author ? $author['authorname'] : null;
        } catch (PDOException $e) {
            // Handle errors
            return null; // Return null if there's an error
        } finally {
            // Close the connection
            $conn = null;
        }
    }
    

    $app->run();

?>