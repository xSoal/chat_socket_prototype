<?php
    $user_id = $_SESSION["user_id"];
    $token = "";

    $q = mysqli_query($link, "SELECT token FROM `users` WHERE id = '$user_id' ");
    $token = mysqli_fetch_assoc($q)["token"];
    

?>



<script type="application/json" id="user_data"><?php
    $user_data = [
        "user_id" => $_SESSION["user_id"],
        "name" => $_SESSION["name"],
        "token" => $token
    ];

    echo json_encode($user_data);
?></script>


<script type="application/json" id="contacts"><?php
    $contacts = [];
    $user_id = $_SESSION["user_id"];
    $q = mysqli_query($link, "SELECT id, name FROM `users` WHERE id <> '$user_id'");
    while($res = mysqli_fetch_assoc($q)){
        $contacts[] = $res;
    }
    echo json_encode($contacts);
?></script>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.26.0/axios.min.js"  ></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qs/6.10.3/qs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment-with-locales.min.js" integrity="sha512-LGXaggshOkD/at6PFNcp2V2unf9LzFq6LE+sChH7ceMTDP0g2kn6Vxwgg7wkPP7AAtX+lmPqPdxB47A0Nz0cMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <link rel="stylesheet" href="style.css">
</head>
<body>



<div id="vueInner">
    <root/>
</div>




<script>
    const socket = new WebSocket("ws://127.0.0.1:8081");
    
    const {user_id, name, token} = getUserData();


    let allMessages = [];
    const contacts = getContacts();
    allMessages = contacts.map(c => {
        return {
            contact_id: c.id,
            messages: []
        }
    });

    socket.onopen = function(){
        socket.send(JSON.stringify({
            action: "authorize",
            user_id,
            token
        }));


        socket.addEventListener('message', e => {
            const {action} = JSON.parse(e.data);
            if(action === 'new_message'){
                const newMessage = JSON.parse(e.data);

                let contactSender = allMessages.find(am => am.contact_id === newMessage.from);
                if(!contactSender) return;

                contactSender.messages.push(newMessage);
            }
        });
    }

    Vue.component('root', {
        data(){
            return {
                selectedChatId: null,
            }
        },
        template: `
            <div class="chatCont">
                <left-aside
                    @selectChat="selectChatHandler"
                />
                <chat-inner
                    :selectedChatId="selectedChatId"
                />
            </div>
        `,
        methods: {
            selectChatHandler(user_id){
                this.selectedChatId = user_id;
            }
        },

    });

    Vue.component('leftAside', {
        data(){
            const contacts = getContacts();
            return {
                contacts,
            }
        },
        template: `
            <div class="leftAside">
                <aside-user
                    v-for="user in contacts"
                    :key="user.user_id"
                    :user="user"
                    @selectChat="selectChatHandler"
                />
            </div>
        `,

        methods: {
            selectChatHandler(user_id){
                this.$emit('selectChat', user_id);
            }
        }

    })


    Vue.component('asideUser', {
        props: ["user"],
        template: `
            <div class="asideUser" @click="onSelectChat">
                {{user.name}}
            </div>
        `,

        methods: {
            onSelectChat(){
                this.$emit("selectChat", this.user.id);
            }
        }
    })



    Vue.component('chatInner', {
        props: ["selectedChatId"],
        data(){
            return {
                allMessages: allMessages
            }
        },
        template: `
            <div class="chatInner">
               <div 
                    class="chat__messages"
                >
                    <div v-if="currentMessages">
                        <message 
                            v-for="(message, i) in currentMessages.messages"
                            :key="i"
                            :message="message"
                            :selectedChatId="selectedChatId"
                        />
                    </div> 
               </div>
               <message-input 
                    v-if="selectedChatId"
                    @sendMessage = 'onSendMessage'
               />
            </div>
        `,
        methods: {
            onSendMessage(message){

                const messageObj = {
                    from: user_id,
                    to: this.selectedChatId,
                    message,
                    date_time: moment().format('YYYY-MM-DD H:mm:ss')
                };

                socket.send(JSON.stringify({
                    action: "send_message",
                    ...messageObj
                }));

                this.currentMessages.messages.push(messageObj);

            }
        },
        mounted(){

            
        },
        computed: {
            currentMessages: {
                get(){
                    return this.allMessages.find(am => am.contact_id === this.selectedChatId)
                },
                set(val, newVal){
                    console.log(val, newVal, 'a;skldaskl;d')
                }
            }
        },
        watch: {

        }
    });


    Vue.component(`message`, {
        props: ["message", "selectedChatId"],
        data(){
            return {
                
            }
        },
        computed: {
            isOwnMessage(){
                return this.message.from === this.selectedChatId
            }
        },
        template: `
            <div class="messageCont" v-if="message">
                <div class="message__text" :class="isOwnMessage ? 'left' : 'right'" >
                    {{message.message}}
                </div>
            </div>
        `,
    });

    Vue.component((`messageInput`),{
        data(){
            return {
                currentMessage: ``
            }
        },
        template: `
            <div class="messageInput__cont">
                <div class="messageInput__inputCont">
                    <input v-model="currentMessage">
                </div>
                <div class="messageInput__submitCont">
                    <button class="" @click="sendMessageHandler">send</button>
                </div>
            </div>
        `,
        methods: {
            sendMessageHandler(){
                this.$emit('sendMessage', this.currentMessage);
                this.currentMessage = ``;
            }
        }
    });

    new Vue({el: "#vueInner"});


    function getContacts(){
        return JSON.parse(document.querySelector('#contacts').innerHTML);
    }

    function getUserData(){
        return  JSON.parse(document.querySelector("#user_data").innerHTML);
    }


    // function getChatMessages(newSelectedChatId){
    //     return axios({
    //         method: 'post',
    //         url: "/ajax.php",
    //         headers: {
    //             "Content-type": "application/json;"
    //         },
    //         data: {
    //             get_messages: {
    //                 author: user_id,
    //                 from_user: newSelectedChatId
    //             }
    //         }
    //     })
    // }










</script>









</body>
</html>










