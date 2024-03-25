# PrivatePublicTexting

## Description

This chat application is built with PHP 8 on the Symfony 6.4 framework. To establish real-time communication it use the Mercure protocol and leverages PostgreSQL for its database needs. The application allows users to add others to their friends list, engage in private conversations with those on the list, and create group chats. It also supports sending images within messages. The frontend is designed with Bootstrap 5 and incorporates Symfony UX Turbo and Stimulus for a dynamic and engaging user interface. 

## Installation

1. Download / Clone repository:
    - ssh: _`git clone git@github.com:Smietan94/PrivatePublicTexting.git`_
    - https: _`git clone https://github.com/Smietan94/PrivatePublicTexting.git`_
2. Create a Docker container by running the following command in your terminal (remember! You use this command from your docker directory): _`docker-compose up -d --build`_
3. Remember that configs in `.env ` are just variables use in development, before switching to production change them!!!
4. Now open docker container _`docker exec -it PrivatePublicTexting-app bash`_
4. Install composer (and all dependecies) _`composer update`_
5. Now You have to carry out migrations _`symfony console doctrine:migrations:migrate`_
6. Last step is to take care of encore/webpack
    - exit docker container
    - install yarn _`yarn upgrade`_
    - and integrate webpack with symfony application _`yarn encore dev`_
    - finally run _`yarn watch`_ to update all css and js changes in real time
7. And now You can go to _`http://127.0.0.1:8000/`_ and use application on local environment

## User manual

1. You can log in or create account
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/9203e3be-7f02-4605-9a57-cfb53a08d368)
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/627a6f61-036e-467e-a993-0df3d14ff565)
(*For now You don't need to provide real email, just remember to keep email format)

3. After registration You will be redirected to search friends route
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/fdff2fc2-8162-4772-80bb-e1223da882e7)

5. Sending request will redirect to friends requests path 
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/df4b52c9-b270-4275-b3d2-ce7eb444be4e)

7. If request is sent/received or users are already friends then sending another request is not possible
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/392d8245-e079-4eb2-94f3-1dd91b0266ac)

9. If finally got friends, You can start chatting with them
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/2eec2d59-da9a-4707-89c7-98c342647905)
    - On side bar there is conversations list, You can search by user name in solo conversation and conversataion member username and conversation name in case of group conversations

10. You can add multiple users to conversation at once
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/404c8c47-6f0c-4b8c-ad88-a1ce341c7428)
    - If You won't set conversation name it will be generated from original members names
    - To create conversation group just send first message

11. You can also send images
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/e56c2fcb-8741-40fb-a50d-8440f63badaf)
    - Adding photos to attachment input field will display imgages previws
    - You can remove unwanted ones by clicking button on the preview top right corner

12. User can manage the conversation by adding/removing users, changing conversation name or leaving/removing conversations (in this case soft delete is preformed) 
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/b70c2496-3f61-4e3d-9c8c-5eeae7684ec0)

14. Clicking image preview will lead to simple photo gallery modal
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/f2bfd01e-376b-4e96-8e9b-47078346511d)

16. In friends list user can manage theirs contact list
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/dce8b8b4-f6db-4d27-a32e-c3f707c92942)

18. User can change their credentials: email, username and password. If want to delete account, soft deletion is performed
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/fe65791d-41b1-437c-b877-9f9878a09a50)

19. User have access to notifications list, which can be ordered by date and filtered by notification type
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/82b191ca-05ce-400e-b8fc-8ec3b72d92af)
![image](https://github.com/Smietan94/PrivatePublicTexting/assets/105523793/640c6263-5aef-4cdc-8bcf-8eb35ff7157a)




## Author:
[Smietan94](https://github.com/Smietan94)
