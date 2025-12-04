// ---- Presence: ping cada 10 segundos ya está en index.php ----

// ---- Modal de selección de audio ----
function crearModalAudio(dispositivos) {
    let modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.left = '0'; modal.style.top = '0';
    modal.style.width = '100vw';
    modal.style.height = '100vh';
    modal.style.background = 'rgba(80,80,80,0.2)';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.innerHTML = `
        <div style="background:#ccc;padding:2em;border-radius:20px;min-width:300px;text-align:center;">
            <h3>Configuración de audio</h3>
            <label>Entrada:</label><br>
            <select id="entrada" style="width:80%;margin-bottom:1em;"></select><br>
            <label>Salida:</label><br>
            <select id="salida" style="width:80%;margin-bottom:1em;"></select><br>
            <button id="guardarAudio">Guardar</button>
            <button id="cerrarAudio">Cerrar</button>
        </div>
    `;
    document.body.appendChild(modal);

    let entradaSel = localStorage.getItem("audioIn") || "";
    let salidaSel = localStorage.getItem("audioOut") || "";
    let entrada = modal.querySelector("#entrada");
    let salida = modal.querySelector("#salida");
    dispositivos.forEach(d => {
        if (d.kind === "audioinput") {
            let o = document.createElement("option");
            o.value = d.deviceId; o.textContent = d.label||d.deviceId;
            if (entradaSel==d.deviceId) o.selected = true;
            entrada.appendChild(o);
        }
        if (d.kind === "audiooutput") {
            let o = document.createElement("option");
            o.value = d.deviceId; o.textContent = d.label||d.deviceId;
            if (salidaSel==d.deviceId) o.selected = true;
            salida.appendChild(o);
        }
    });
    modal.querySelector("#guardarAudio").onclick = () => {
        localStorage.setItem("audioIn", entrada.value);
        localStorage.setItem("audioOut", salida.value);
        document.body.removeChild(modal);
    };
    modal.querySelector("#cerrarAudio").onclick = () => {
        document.body.removeChild(modal);
    };
}

// Lanzar modal al pulsar icono
document.addEventListener("DOMContentLoaded", () => {
    let altavoz = document.getElementById("audio_config");
    if (altavoz) {
        altavoz.onclick = () => {
            navigator.mediaDevices.enumerateDevices().then(crearModalAudio);
        };
    }

// ---- WebRTC signaling JS ----
let localStream = null, peers = {};
let SIGNALING = "signaling.php";

// Función para iniciar stream local
async function iniciarStream() {
    let audioInId = localStorage.getItem("audioIn");
    try {
        let constraints = {audio: audioInId ? {deviceId: {exact: audioInId}} : true, video:false};
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        return localStream;
    } catch(e) {
        alert("No se pudo acceder al microfono.");
        return null;
    }
}

// Cambiar color de botón
function marcarBoton(ip, color) {
    let btn = document.querySelector('.icombtn[data-ip="'+ip+'"]');
    if (btn) {
        btn.classList.remove("green","gray","red");
        btn.classList.add(color);
    }
}

// Signaling: enviar y polling (recibir)
function enviarSenal(userDestino, ipDestino, mensaje) {
    fetch(SIGNALING, {
        method: "POST",
        body: JSON.stringify({
            sala: window.__salaActivo,
            user: window.__miIP,
            target: ipDestino,
            action: 'send',
            message: mensaje
        })
    });
}
function recibirSenal(ipDestino, callback) {
    let poll = setInterval(()=>{
        fetch(SIGNALING, {
            method:"POST",
            body:JSON.stringify({
                sala:window.__salaActivo, user:window.__miIP, target:ipDestino, action:'receive'
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
    return poll;
}

// Peer: crear para llamada
function crearPeer(ipDestino, userDestino) {
    let pc = new RTCPeerConnection();
    iniciarStream().then(stream => {
        if (stream) stream.getTracks().forEach(track => pc.addTrack(track, stream));
    });
    pc.onicecandidate = e => {
        if (e.candidate) enviarSenal(userDestino, ipDestino, {ice: e.candidate});
    };
    pc.ontrack = e => {
        let audio = document.getElementById("audio_remote_"+ipDestino);
        if (!audio) {
            audio = document.createElement("audio");
            audio.id = "audio_remote_"+ipDestino;
            audio.autoplay = true;
            document.body.appendChild(audio);
        }
        audio.srcObject = e.streams[0];
        // Cambiar color del botón a rojo mientras reproduce
        marcarBoton(ipDestino,"red");
    };
    peers[ipDestino] = pc;
    return pc;
}

// Llamada por botón
document.querySelectorAll(".icombtn.green").forEach(btn=>{
    let ipDest = btn.getAttribute('data-ip');
    let userDest = btn.getAttribute('data-user');
    let tStart = 0, peer=null, poll=null;
    btn.addEventListener("mousedown", async e=>{
        tStart = Date.now();
        peer = crearPeer(ipDest, userDest);
        let offer = await peer.createOffer();
        await peer.setLocalDescription(offer);
        enviarSenal(userDest, ipDest, {offer: offer});
        marcarBoton(ipDest,"red"); // Llamada activa

        poll = recibirSenal(ipDest, async (msg) => {
            if (msg.answer) {
                await peer.setRemoteDescription(new RTCSessionDescription(msg.answer));
            }
            if (msg.ice) {
                await peer.addIceCandidate(new RTCIceCandidate(msg.ice));
            }
        });
    });
    btn.addEventListener("mouseup", e=>{
        let dur = Date.now()-tStart;
        tStart = 0;
        if (peer) peer.close();
        if (poll) clearInterval(poll);
        marcarBoton(ipDest,"green");
    });
    btn.addEventListener("mouseleave", e=>{
        if (peer) peer.close();
        if (poll) clearInterval(poll);
        marcarBoton(ipDest,"green");
        tStart=0;
    });
});

// Botón todos: enviar a todos los conectados (sin multiconferencia real; solo emite a todos)
let confBtn = document.querySelector(".icombtn[data-conf='1']");
if (confBtn) {
    confBtn.addEventListener("mousedown", async e=>{
        // Llama a todos en la sala, pero sólo emite (no escucha), como un broadcast
        document.querySelectorAll('.icombtn.green').forEach(async btn=>{
            let ipDest = btn.getAttribute('data-ip');
            let userDest = btn.getAttribute('data-user');
            if (ipDest!=="ALL" && ipDest!==window.__miIP) {
                let peer = crearPeer(ipDest, userDest);
                let offer = await peer.createOffer();
                await peer.setLocalDescription(offer);
                enviarSenal(userDest, ipDest, {offer: offer});
                marcarBoton(ipDest,"red");
            }
        });
    });
    confBtn.addEventListener("mouseup", e=>{
        document.querySelectorAll('.icombtn.red').forEach(btn=>{
            let ipDest = btn.getAttribute('data-ip');
            if (peers[ipDest]) peers[ipDest].close();
            marcarBoton(ipDest,"green");
        });
    });
}
});