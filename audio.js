/**
 * audio.js
 * Requiere que cada botón tenga atributo data-ip (IP destino), data-index (número de botón) y data-sala (sala clave).
 */
let localStream = null;
let peers = {}; // Por IP destino
let salaActivo = document.body.getAttribute('data-sala') || "SALA_1";
let miIP = document.body.getAttribute('data-ip');       // Tu propia IP (se pasa desde PHP)
let miUser = document.body.getAttribute('data-user');   // Tu propio nombre usuario
const SIGNALING = "signaling.php";

function seleccionarDispositivos() {
    // Modal/config para alternar entrada/salida
    navigator.mediaDevices.enumerateDevices().then(devices => {
        let audioIn = devices.filter(d => d.kind==="audioinput");
        let audioOut = devices.filter(d => d.kind==="audiooutput");
        // Puedes mostrar una UI para elegir y guardar en localStorage
        // localStorage.setItem("audioIn", seleccion)
        // localStorage.setItem("audioOut", seleccion)
    });
}

async function iniciarStream() {
    if (localStream) return localStream;
    try {
        localStream = await navigator.mediaDevices.getUserMedia({audio:true, video:false});
        return localStream;
    } catch(e) {
        alert("No se pudo acceder al microfono.");
        return null;
    }
}

function crearPeer(ipDestino, userDestino, conferencia=false) {
    let pc = new RTCPeerConnection();
    iniciarStream().then(stream => {
        if (!stream) return;
        stream.getTracks().forEach(track => pc.addTrack(track, stream));
    });

    // Responder ICE, ofertar, etc.
    pc.onicecandidate = e => {
        if (e.candidate) {
            enviarSenal(userDestino, ipDestino, {ice: e.candidate});
        }
    };
    pc.ontrack = e => {
        // Reproducir audio entrante con <audio> (puede añadir a la UI)
        let audio = document.getElementById("audio_remote_"+ipDestino);
        if (!audio) {
            audio = document.createElement("audio");
            audio.id = "audio_remote_"+ipDestino;
            audio.autoplay = true;
            document.body.appendChild(audio);
        }
        audio.srcObject = e.streams[0];
    };
    peers[ipDestino] = pc;
    return pc;
}

function enviarSenal(userDestino, ipDestino, mensaje) {
    fetch(SIGNALING, {
        method: "POST",
        body: JSON.stringify({
            sala: salaActivo,
            user: miIP,
            target: ipDestino,
            action: 'send',
            message: mensaje
        })
    });
}
function recibirSenal(ipDestino, callback) {
    // polling cada 1s
    setInterval(()=>{
        fetch(SIGNALING, {
            method:"POST",
            body:JSON.stringify({
                sala:salaActivo, user:miIP, target:ipDestino, action:'receive'
            })
        })
        .then(r=>r.text())
        .then(t=>{
            if (t && t!=="null") {
                let obj = JSON.parse(t);
                callback(obj);
            }
        });
    }, 1000);
}

// Detectar pulsaciones en los botones
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".icombtn.green").forEach(btn=>{
        let ipDest = btn.getAttribute('data-ip');
        let userDest = btn.getAttribute('data-user');
        let tStart = 0, llamadaActiva = false, peer=null;
        btn.addEventListener("mousedown", e=>{
            tStart = Date.now();
        });
        btn.addEventListener("mouseup", async e=>{
            let dur = Date.now()-tStart;
            tStart = 0;
            peer = crearPeer(ipDest, userDest);
            llamadaActiva=true;
            if (dur<700) {
                // llamada breve: iniciar peer/conexión, finalizar tras 3 s
                let offer = await peer.createOffer();
                await peer.setLocalDescription(offer);
                enviarSenal(userDest, ipDest, {offer: offer});
                setTimeout(()=>{
                    peer.close();
                    llamadaActiva=false;
                }, 3000);
            } else {
                // llamada larga: mantener mientras pulsado
                let offer = await peer.createOffer();
                await peer.setLocalDescription(offer);
                enviarSenal(userDest, ipDest, {offer: offer});
                // Finaliza cuando se suelta el mouse
            }
            // Recibir señalización
            recibirSenal(ipDest, async (msg) => {
                if (msg.answer) {
                    await peer.setRemoteDescription(new RTCSessionDescription(msg.answer));
                }
                if (msg.ice) {
                    await peer.addIceCandidate(new RTCIceCandidate(msg.ice));
                }
            });
        });
        btn.addEventListener("mouseleave", e=>{
            // fin llamada
            if (peer) peer.close();
            llamadaActiva=false;
            tStart=0;
        });
    });

    // Botón conferencia (primero), conecta con todos los usuarios de la sala
    let confBtn = document.querySelector(".icombtn[data-conf='1']");
    if (confBtn) {
        confBtn.addEventListener("mousedown", async e=>{
            // Crear peer con todos los IPs en verde en la sala
        });
        // etc...
    }

    // Icono altavoz: selección modal
    let altavoz = document.getElementById("audio_config");
    if (altavoz) {
        altavoz.addEventListener("click", seleccionarDispositivos);
    }
});