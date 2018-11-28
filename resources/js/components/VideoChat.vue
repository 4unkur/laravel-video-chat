<template>
    <div>
        <span v-if="id">My caller ID is: {{ id }}</span>
        <br>
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
                id: null,
                users: [],
                room: null,
                caller: null,
                localUserMedia: null,
                channel: null,
                showEndCallButton: false,
                prepared: false,
                configuration: {
                    iceServers: [{
                        urls: 'stun:stun.l.google.com:19302'
                    }]
                }
            }
        },
        created() {
            this.GetRTCPeerConnection();
            this.GetRTCIceCandidate();
            this.prepareCaller();

            this.channel = Echo.join(`interview.${this.chat.hash}`)

            this.channel
                .here((users) => {
                    this.id = this.channel.pusher.channels.channels[this.channel.name].members.me.id
                    users.forEach((user) => {
                        if (user.id != this.id) {
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
                    if (user.id == this.room) {
                        this.endCall()
                    }
                })

                .listenForWhisper("client-candidate", message => {
                    //Listening for the candidate message from a peer sent from onicecandidate handler
                    if (message.room == this.room) {
                        // I have no idea what this shit does, so I commented it
                        this.caller.addIceCandidate(new RTCIceCandidate(message.candidate))
                    }
                })
                .listenForWhisper("client-sdp", message => {
                    //Listening for Session Description Protocol message with session details from remote peer

                    if (message.room != this.id) {
                        return;
                    }

                    console.log("sdp received");
                    let answer = confirm(`You have a call from: ${message.from}. Would you like to answer?`);
                    if (!answer) {
                        return this.channel.whisper("client-reject", {"room": message.room, "rejected": this.id});
                    }

                    this.room = message.room;
                    this.caller.setRemoteDescription(message.sdp).then(() => {
                        this.getCamera().then(stream => {
                            this.localUserMedia = stream;
                            this.toggleEndCallButton();
                            this.$refs.selfview.srcObject = stream
                            stream.getTracks().forEach((track) => {
                                this.caller.addTrack(track, stream);
                            });
                            if (this.caller.remoteDescription.type === 'offer') {
                                this.caller.createAnswer().then(answer => {
                                    this.caller.setLocalDescription(answer);
                                    this.channel.whisper("client-answer", {
                                        "sdp": answer,
                                        "room": this.room
                                    });
                                })
                            }
                        }).catch(error => {
                            console.log('an error occured', error);
                        })
                    })
                })
                .listenForWhisper("client-answer", answer => {
                    if (answer.room == this.room) {
                        console.log("answer received");
                        this.caller.setRemoteDescription(answer.sdp);
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
                this.caller = new window.RTCPeerConnection(this.configuration);
                //Listen for ICE Candidates and send them to remote peers
                this.caller.onicecandidate = event => {
                    if (event.candidate) {
                        console.log("onicecandidate called");
                        this.channel.whisper("client-candidate", {
                            "candidate": event.candidate,
                            "room": this.room
                        });
                    }
                };
                //onaddstream handler to receive remote feed and show in remoteview video element
                this.caller.ontrack = event => {
                    console.log("onaddstream called");
                    this.$refs.remoteview.srcObject = event.streams[0];
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
                this.getCamera()
                    .then(stream => {
                        this.$refs.selfview.srcObject = stream
                        this.toggleEndCallButton();
                        // this.caller.addStream(stream);
                        stream.getTracks().forEach((track) => {
                            this.caller.addTrack(track, stream);
                        });
                        this.localUserMedia = stream;
                        this.caller.createOffer().then(offer => {
                            this.caller.setLocalDescription(offer).then(() => {
                                this.channel.whisper("client-sdp", {
                                    "sdp": offer,
                                    "room": user.id,
                                    "from": this.id
                                });
                                this.room = user.id;
                            })
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