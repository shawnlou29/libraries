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

### Endpoint 1: `/users/register`  

- **Method**: POST  

#### **Request Body**:  
```json
{
  "email": "shawnrimando123@gmail.com",
  "username": "ShawnYi",
  "password": "ShawnRimando"
}

#### **Response**:
  - **Status Code*: 200 
```json
{
  "status": "success",
  "data": null
}
