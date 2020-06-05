# Asservatenmanagement-System / Evidence object management system

This program was created during an internship at DigiTrace GmbH.
Its main purpose is to provide a digital summary of evidences and track changes. As a result, this tool implements a chain of custody, which is important for law enforcements and courts.


Currently this tool provides following functions:  
  * Managing objects like evidences, equipment, records and harddrives   
  * Keep track of objects, like when an action is done by user  
  * A simple case management, which is mandatory to keep track for the objects  

It is important to understand, that the software **is NOT a replacement for the analog documentation**. It complements it, by giving a faster possibility to check the status of the object.

This software is build with the symfony framework and can be deployed, if the symfony and composer requirements are fulfilled. For **testing purposes** it is possible to run the application within a docker container. By doing this, it is required to build the Container locally.

To build the container, following commands has to the executed

`git clone https://github.com/DigiTrace/AM-System && cd AM-System`  
`sudo docker build -t AMSystem .`  

The build process needs some time, due the preparation of the database and performing application testing.
If the build process is finished, the container *AM-System* will be created.
To run the container, following command must be executed:

`sudo docker run -t -p 8080:8080 --name amtest  AMSystem `

The website can be found at `http://127.0.0.1:8080`  

Following accounts are predefined:  
  * User/test  
  * User2/test  
  * User3/test  
  * Admin/test  
