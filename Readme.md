
sudo docker build -t am .

sudo docker run -t -p 8080:8080 --name amtest  am 
