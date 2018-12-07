// JavaScript Document
  function decodeHTMLEntities (str) {
    if(str && typeof str === 'string') {
      // strip script/html tags
      str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
      str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
      element.innerHTML = str;
      str = element.textContent;
      element.textContent = '';
    }

    return str;
  }
  function load_jsmind(text){
        var mind =  decodeHTMLEntities(text);

        var options = {
            container:'jsmind_container',
            editable:true,
            theme:'default'
        }
        var jm = jsMind.show(options,mind);
   }
