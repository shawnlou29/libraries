# Library API using SLIM Framework
Created by Shawn Lou Yi Rimando 4D
Submitted to Dr. Manny Hortizuela

## Introduction
--*Overview**: The [Library] is a robust and efficient API to operate by the library admin . Built with the Slim Framework, this API is designed for developers and system integrators seeking a lightweight yet powerful solution for rapid web application and API development.
--*Token Storage**: For secure interactions, the API requires tokens to be stored and managed properly. It is recommended to store tokens in secure and encrypted storage mechanisms to prevent unauthorized access and ensure user data protection. 
--*User Verification**: To ensure data security and proper usage to validate the identity of users before granting access to resources. 
--*Development Tools**: The API is developed using the **Slim Framework***, a lightweight PHP micro-framework known for its simplicity, flexibility, and powerful routing capabilities. This choice allows for rapid development, easy integration of third-party libraries, and efficient handling of HTTP requests and responses. 

### Endpoints

### Endpoints 1: [/users/register]

- *Method*: POST
  
- **Request Body**:
   ```json
{
  "email": "shawnrimando123@gmail.com",
  "username": "ShawnYi",
  "password": "ShawnRimando"
}

- **Response***:
    - Status Code: 200
  ```json
{
    "status": "success",
    "data": null
}
- **Error Response*:
    - Status Code: 400
  ```json
{
    "status": "fail",
    "data": {
        "Message": "Invalid Email! Try another one."
    }
}

- **Error Response*:
    - Status Code: 500
  ```json
{
    "status": "fail",
    "data": {
        "Message": "Registration failed."
    }
}
