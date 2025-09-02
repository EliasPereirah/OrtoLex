const fileInput = document.querySelector('#js_file');

fileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    const fileName = file.name;
    const parts = fileName.split(".");
    const ext = parts[parts.length - 1].toLowerCase();
    if(ext !== 'pdf' && ext !== 'txt'){
        //alert("Apenas PDF e arquivo de texto são aceitos");
    }else{
        document.querySelector("button").onclick = ()=>{
            let processing = document.createElement("div");
            processing.setAttribute("id", "processing");
            processing.innerHTML = "Aguarde, processando arquivo. <span id='loader'></span>";
            document.querySelector('.app').append(processing);
        }
    }
});
