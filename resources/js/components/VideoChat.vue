<template>
    <div>
        <span v-if="id">My caller ID is: {{ id }}</span>
        <video ref="selfview" autoplay></video>
        <video ref="remoteview" autoplay></video>
        <button v-show="showEndCallButton" @click="endCurrentCall">End Call</button>
        <div>
            <ul class="list-group">
                <li class="list-group-item" v-for="user in users" :key="user.id">
                    {{ user.name }}
                    <button class="btn btn-primary float-right" type="button" @click="callUser(user)">Call</button>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['chat'],
        data() {
            return {
                //usersOnline, id, users = [], room, caller, localUserMedia
                usersOnline: [],
                id: null,
                users: [],
                room: null,
                caller: null,
                localUserMedia: null,
                channel: null,
                showEndCallButton: false
            }
        },
        created() {
            this.GetRTCPeerConnection();
            this.GetRTCSessionDescription();
            this.GetRTCIceCandidate();
            this.prepareCaller();

            this.channel = Echo.join(`interview.${this.chat.hash}`)

            this.channel
                .here((users) => {
                    this.usersOnline = users.count;
                    this.id = this.channel.pusher.channels.channels[this.channel.name].members.me.id
                    users.forEach((user) => {
                        if (user.id !== this.id) {
                            this.users.push({id: user.id, name: user.name})
                        }
                    });
                })
                .joining((user) => {
                    this.users.push({id: user.id, name: user.name})
                })
                .leaving((user) => {
                    let index = this.users.indexOf(user.id);
                    this.users.splice(index, 1);
                    if (user.id === this.room) {
                        endCall()
                    }
                })


                .listenForWhisper("client-candidate", message => {
                    //Listening for the candidate message from a peer sent from onicecandidate handler
                    if (message.room == this.room) {
                        let candidate = new RTCIceCandidate(message.candidate)
                        this.caller.addIceCandidate(candidate).catch(error => console.log(error))
                    }
                })
                .listenForWhisper("client-sdp", message => {
                    //Listening for Session Description Protocol message with session details from remote peer

                    if (message.room != this.id) {
                        return;
                    }

                    console.log("sdp received");
                    let answer = confirm(`You have a call from: ${message.from}
                    Would you like to answer?`);
                    if (!answer) {
                        return this.channel.whisper("client-reject", {"room": message.room, "rejected": this.id});
                    }

                    this.room = message.room;
                    this.caller.setRemoteDescription(new RTCSessionDescription(message.sdp));
                    this.getCamera().then(stream => {
                        this.localUserMedia = stream;
                        this.toggleEndCallButton();
                        this.$refs.selfview.srcObject = stream
                        this.caller.addStream(stream);
                        this.caller.createAnswer().then(sdp => {
                            this.caller.setLocalDescription(new RTCSessionDescription(sdp));
                            this.channel.whisper("client-answer", {
                                "sdp": sdp,
                                "room": this.room
                            });
                        });
                    }).catch(error => {
                        console.log('an error occured', error);
                    })
                })
                .listenForWhisper("client-answer", function (answer) {
                    //Listening for answer to offer sent to remote peer
                    if (answer.room == this.room) {
                        console.log("answer received");
                        this.caller.setRemoteDescription(new RTCSessionDescription(answer.sdp));
                    }
                })

                .listenForWhisper("client-reject", answer => {
                    if (answer.room == this.room) {
                        console.log("Call declined");
                        alert(`Call to ${answer.rejected} was politely declined`);
                        this.endCall();
                    }
                })
                .listenForWhisper("client-endcall", answer => {
                    if (answer.room == this.room) {
                        console.log("Call Ended");
                        this.endCall();
                    }
                });
        },
        methods: {
            prepareCaller() {
                //Initializing a peer connection
                this.caller = new window.RTCPeerConnection();
                //Listen for ICE Candidates and send them to remote peers
                this.caller.onicecandidate = event => {
                    if (!event.candidate) {
                        return;
                    }
                    console.log("onicecandidate called");
                    this.onIceCandidate(this.caller, event);
                };
                //onaddstream handler to receive remote feed and show in remoteview video element
                this.caller.ontrack = event => {
                    console.log("onaddstream called");
                    this.$refs.remoteview.srcObject = event.stream;
                };
            },
            getCamera() {
                //Get local audio/video feed and show it in selfview video element
                return navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
            },
            //Create and send offer to remote peer on button click
            callUser(user) {
                // this.caller.setRemoteDescription(new RTCSessionDescription());
                this.getCamera()
                    .then(stream => {
                        this.$refs.selfview.srcObject = stream
                        this.toggleEndCallButton();
                        this.caller.addStream(stream);
                        this.localUserMedia = stream;
                        this.caller.createOffer()
                            .then(description => {
                                this.caller.setLocalDescription(new RTCSessionDescription(description));
                                this.channel.whisper("client-sdp", {
                                    "sdp": description,
                                    "room": user.id,
                                    "from": this.id
                                });
                                this.room = user.id;
                            });
                    })
                    .catch(error => {
                        console.log('an error occurred', error);
                    })
            },
            endCall() {
                this.room = null;
                this.caller.close();
                for (let track of this.localUserMedia.getTracks()) {
                    track.stop()
                }
                this.prepareCaller();
                this.toggleEndCallButton();
            },

            endCurrentCall() {
                this.channel.whisper("client-endcall", {
                    "room": this.room
                });
                this.endCall();
            },
            //Send the ICE Candidate to the remote peer
            onIceCandidate(peer, event) {
                if (event.candidate) {
                    this.channel.whisper("client-candidate", {
                        "candidate": event.candidate,
                        "room": this.room
                    });
                }
            },

            toggleEndCallButton() {
                this.showEndCallButton = !this.showEndCallButton
            },

            GetRTCIceCandidate() {
                window.RTCIceCandidate = window.RTCIceCandidate || window.webkitRTCIceCandidate ||
                    window.mozRTCIceCandidate || window.msRTCIceCandidate;
                return window.RTCIceCandidate;
            },

            GetRTCPeerConnection() {
                window.RTCPeerConnection = window.RTCPeerConnection || window.webkitRTCPeerConnection ||
                    window.mozRTCPeerConnection || window.msRTCPeerConnection;
                return window.RTCPeerConnection;
            },

            GetRTCSessionDescription() {
                window.RTCSessionDescription = window.RTCSessionDescription || window.webkitRTCSessionDescription ||
                    window.mozRTCSessionDescription || window.msRTCSessionDescription;
                return window.RTCSessionDescription;
            }
        }
    }
</script>

<style scoped>
    video {
        /* video border */
        width: 200px;
        border: 1px solid #ccc;
        padding: 20px;
        margin: 10px;
        border-radius: 20px;
    }

    #list ul li:hover {
        box-shadow: 10px 10px 20px #000000;
    }
</style>