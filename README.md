# Library API using SLIM Framework

**Created by:** Shawn Lou Yi Rimando 4D  
**Submitted to:** Dr. Manny Hortizuela  

---

## Introduction

### Overview  
The **Library API** is a robust and efficient solution designed to streamline library administration tasks. Built with the Slim Framework, it offers developers and system integrators a lightweight yet powerful tool for rapid web application and API development.

### Key Features  

- **Token Storage**  
  The API requires tokens for secure interactions. It is recommended to store tokens in secure and encrypted storage mechanisms to prevent unauthorized access and ensure user data protection.  

- **User Verification**  
  To ensure data security and proper usage, the API validates the identity of users before granting access to resources.  

- **Development Tools**  
  The API is developed using the **Slim Framework**, a lightweight PHP micro-framework known for its simplicity, flexibility, and powerful routing capabilities. This choice allows for rapid development, easy integration of third-party libraries, and efficient handling of HTTP requests and responses.  

---

## Endpoints  
-----------------------------------------------------------------------------------
## Endpoint 1: `/users/register`  

- **Method**: POST  

**Request Body:**  
```json
{
  "email": "shawnrimando123@gmail.com",
  "username": "ShawnYi",
  "password": "ShawnRimando"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
    "status": "success",
    "data": null
}
```
- **Error Respond**: Missing Field
  - **Status Code**: 400
```json
{
    "status": "fail",
    "data": {
        "Message": "Invalid Email! Try another one."
    }
}
```
- **Error Respond**: Registration Failure
  - **Status Code**: 500
```json
{
    "status": "fail",
    "data": {
        "Message": "Registration failed."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 2: `/users/login`  

- **Method**: POST  

**Request Body**:  
```json
{
  "email": "shawnrimando123@gmail.com",
  "password": "ShawnRimando"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MjgwNjAsImV4cCI6MTczMTc0MjQ2MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoiU2hhd25ZaSIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.m5wnQ8l8z0wSAx2tpwFe4LINIBmZZ-tlyPPHS2-5nZk"
}
```
- **Error Respond**: Invalid Credentials 
  - **Status Code**: 401
```json
{
    "status": "fail",
    "data": {
        "Message": "Invalid email or password."
    }
}
```
- **Error Respond**: Login Failure 
  - **Status Code**: 500
```json
{
    "status": "fail",
    "data": {
        "Message": "Login failed."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 3: `/books/add`  

- **Method**: POST  

**Request Body**:  
```json
{
    "author": "J.K. Rowling",
    "title": "Harry Potter and the Deathly Hallows",
    "genre": "Fantasy",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MjgwNjAsImV4cCI6MTczMTc0MjQ2MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoiU2hhd25ZaSIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.m5wnQ8l8z0wSAx2tpwFe4LINIBmZZ-tlyPPHS2-5nZk"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3Mjk5NDksImV4cCI6MTczMTc0NDM0OSwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.E2aQLsVXZLNMk-8RGgRWGStsekGb3zqEDeTgOlIpM1k"
}
```
**Request Body**: (Adding more books)
```json
{
    "author": "J.K. Rowling",
    "title": "Harry Potter and the Deathly Hallows",
    "genre": "Fantasy",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MjgwNjAsImV4cCI6MTczMTc0MjQ2MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoiU2hhd25ZaSIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.m5wnQ8l8z0wSAx2tpwFe4LINIBmZZ-tlyPPHS2-5nZk"
}
```
**Respond**: Success (adding more books same name)
  - **Status Code**: 200 
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzAwMzYsImV4cCI6MTczMTc0NDQzNiwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.5SYKts7GSv8QxtAVsVs9vJ6EzA8oXMFvRgqyR7pUNZY"
}
```
**Request Body**: (Adding more books different name)
```json
{
    "author": "Greta Gerwig",
    "title": "Barbie",
    "genre": "Fantasy Comedy",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzAwMzYsImV4cCI6MTczMTc0NDQzNiwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.5SYKts7GSv8QxtAVsVs9vJ6EzA8oXMFvRgqyR7pUNZY"
}
```
**Respond**: Success (adding more books)
  - **Status Code**: 200 
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzAzODAsImV4cCI6MTczMTc0NDc4MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.4Idg3P9Rbws1CdsgRtkFyZVoqjOgcx9_JjsS3_rjpAE"
}
```
- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
- **Error Respond**: Access Denied
  - **Status Code**: 403
```json
{
    "status": "fail",
    "data": {
        "Message": "Access Denied. Only admins can add books."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 4: `/books/update`

- **Method**: POST  

**Request Body**:  
```json
{
  "author": "David Heyman",
  "title": "8 films",
  "genre":"Sci-Fi",
  "bookCode": "624BO",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzAzODAsImV4cCI6MTczMTc0NDc4MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.4Idg3P9Rbws1CdsgRtkFyZVoqjOgcx9_JjsS3_rjpAE"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzA1NzcsImV4cCI6MTczMTczNDE3NywiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.Fd8qZxyWM4HFp6TLZdzk_IkwaeCe1irt73ifxDN--08"
}
```
- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
- **Error Respond**: Access Denied
  - **Status Code**: 403
```json
{
    "status": "fail",
    "data": {
        "Message": "Access Denied. Only admins can update books."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 5: `/books/delete`

- **Method**: DELETE  

**Request Body**:  
```json
{
  "bookCode": "624BO",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzA1NzcsImV4cCI6MTczMTczNDE3NywiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.Fd8qZxyWM4HFp6TLZdzk_IkwaeCe1irt73ifxDN--08"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzA3NzQsImV4cCI6MTczMTczNDM3NCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.q5sESB9bg6q2QjASQ54kSwDGOAX2AWogablqzYk577I"
}
```
- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
- **Error Respond**: Access Denied
  - **Status Code**: 403
```json
{
    "status": "fail",
    "data": {
        "Message": "Access Denied. Only admins can add books."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 6: `/books/displayAll`

- **Method**: GET  

**Request Body**:  
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzA3NzQsImV4cCI6MTczMTczNDM3NCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.q5sESB9bg6q2QjASQ54kSwDGOAX2AWogablqzYk577I"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzA4MjcsImV4cCI6MTczMTczNDQyNywiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.yj_FmopCzi0jRXDmdh4WSm74Y9q-mPitsBVsNnhuMZ4",
  "data": [
    {
      "bookid": 419,
      "title": "Harry Potter and the Deathly Hallows",
      "genre": "Fantasy",
      "bookCode": "803OY",
      "authorid": 116,
      "authorname": "J.K. Rowling"
    },
    {
      "bookid": 421,
      "title": "Barbie",
      "genre": "Fantasy Comedy",
      "bookCode": "852OZ",
      "authorid": 117,
      "authorname": "Greta Gerwig"
    }
  ]
}
```

- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 7: `/books/displayauthorsbooks` 
- **Method**: GET  

**Request Body**:  
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzA4MjcsImV4cCI6MTczMTczNDQyNywiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.yj_FmopCzi0jRXDmdh4WSm74Y9q-mPitsBVsNnhuMZ4",
  "authorname":  "J.K. Rowling"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzA5ODAsImV4cCI6MTczMTczNDU4MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.n7HhVjRYnW3LYudd4ta_7lMg-v25Ovkx14f5JpYiGpY",
  "data": [
    {
      "bookid": 419,
      "title": "Harry Potter and the Deathly Hallows",
      "genre": "Fantasy",
      "bookCode": "803OY",
      "authorid": 116,
      "authorname": "J.K. Rowling"
    }
  ]
}
```

- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 8: `/books/displaytitlebooks` 
- **Method**: GET  

**Request Body**:  
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzA5ODAsImV4cCI6MTczMTczNDU4MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.n7HhVjRYnW3LYudd4ta_7lMg-v25Ovkx14f5JpYiGpY",
  "booktitle": "Barbie"
}

```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzEwNjQsImV4cCI6MTczMTczNDY2NCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.n-5ntpdfnB_zxDmoQCpKv8HKTCXEkiljNK6L9gwhjQY",
  "data": [
    {
      "bookid": 421,
      "title": "Barbie",
      "genre": "Fantasy Comedy",
      "bookCode": "852OZ",
      "authorid": 117,
      "authorname": "Greta Gerwig"
    }
  ]
}
```

- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 9: `/books/displaygenrebooks`
- **Method**: GET  

**Request Body**:  
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzEwNjQsImV4cCI6MTczMTczNDY2NCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.n-5ntpdfnB_zxDmoQCpKv8HKTCXEkiljNK6L9gwhjQY",
  "bookgenre": "Fantasy Comedy"
}

```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzExMDUsImV4cCI6MTczMTczNDcwNSwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.46QZ3SwKkB5Kp0y6WF8YN2sRj8_cpZV-Z0cB1fS2yB4",
  "data": [
    {
      "bookid": 421,
      "title": "Barbie",
      "genre": "Fantasy Comedy",
      "bookCode": "852OZ",
      "authorid": 117,
      "authorname": "Greta Gerwig"
    }
  ]
}
```
- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 10: `/authors/add`
- **Method**: POST  

**Request Body**:  
```json
{
  "authorname": "Charles Perrault",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzExMDUsImV4cCI6MTczMTczNDcwNSwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.46QZ3SwKkB5Kp0y6WF8YN2sRj8_cpZV-Z0cB1fS2yB4"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
"status":"success",
"new_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzExODcsImV4cCI6MTczMTczNDc4NywiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.Xses9UTz_Xe1bZd3ctdjbyBnshToahYPaPXm-CMNIj8"
```

- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 11: `/authors/update` 
- **Method**: POST  

**Request Body**:  
```json
{
    "authorid": 119,  
    "authorname": "Charles P",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzExODcsImV4cCI6MTczMTczNDc4NywiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.Xses9UTz_Xe1bZd3ctdjbyBnshToahYPaPXm-CMNIj8"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzEyNDcsImV4cCI6MTczMTczNDg0NywiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.GVv5q0rUp-GOxyTfl6nFBQeDa2Y6iL1JANWNM-5_W-U"
}
```

- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 12: `/authors/delete` 
- **Method**: DELETE  

**Request Body**:  
```json
{
    "authorid": 119,  
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzEyNDcsImV4cCI6MTczMTczNDg0NywiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.GVv5q0rUp-GOxyTfl6nFBQeDa2Y6iL1JANWNM-5_W-U"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzEyNzAsImV4cCI6MTczMTczNDg3MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.5I2xzhRPpM8hDZ-bNvCRBLDiFUsEDFmQCnlkVAXPdIE"
}
```

- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
- **Error Respond**: Invalid ID Author
  - **Status Code**: 404
```json
{
    "status": "fail",
    "data": {
        "Message": "Invalid Author ID."
    }
}
```
-----------------------------------------------------------------------------------
## Endpoint 13: `/authors/display`
- **Method**: GET  

**Request Body**:  
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzEyNzAsImV4cCI6MTczMTczNDg3MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.5I2xzhRPpM8hDZ-bNvCRBLDiFUsEDFmQCnlkVAXPdIE"
}
```
**Respond**: Success
  - **Status Code**: 200
```json
{
  "status": "success",
  "new_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbGlicmFyeS5vcmciLCJhdWQiOiJodHRwOi8vbGlicmFyeS5jb20iLCJpYXQiOjE3MzE3MzEyODAsImV4cCI6MTczMTczNDg4MCwiZGF0YSI6eyJ1c2VyaWQiOjIzLCJuYW1lIjoicm9vdCIsImFjY2Vzc19sZXZlbCI6ImFkbWluIn19.HOQtOZ-6NRSJO1-ZuasS_zjwefxoCzWZga2ANcRN4zQ",
  "data": [
    {
      "authorid": 116,
      "authorname": "J.K. Rowling"
    },
    {
      "authorid": 117,
      "authorname": "Greta Gerwig"
    },
    {
      "authorid": 118,
      "authorname": "David Heyman"
    }
  ]
}
```

- **Error Respond**: Invalid Token
  - **Status Code**: 200
```json
{
    "status": "fail",
    "data": {
        "Message": "Token is invalid or outdated."
    }
}
```
- **Error Respond**: No Authors Found
  - **Status Code**: 404
```json
{
    "status": "fail",
    "Message": "No authors found."
}
```
