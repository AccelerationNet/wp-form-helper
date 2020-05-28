if(typeof(WPFH) == 'undefined') WPFH = {};
console.log('Loading wpfh');

WPFH.trimAndNullify = function trimAndNullify(it) {
  if(!it) return null;
  it = it.toString().trim();
  if(it.length == 0) return null;
  return it;
};


WPFH.debounce = function(func, wait, immediate) {
    var timeout;
    return function() {
      var context = this, args = arguments;
      var later = function() {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };
      if (immediate && !timeout) func.apply(context, args);
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
};

WPFH.throttle = function throttle (func, wait) {
    var context, args, timeout, throttling, more, result;
    var whenDone = WPFH.debounce(function(){ more = throttling = false; }, wait);
    return function() {
      context = this; args = arguments;
      var later = function() {
        timeout = null;
        if (more) func.apply(context, args);
        whenDone();
      };
      if (!timeout) timeout = setTimeout(later, wait);
      if (throttling) {
        more = true;
      } else {
        result = func.apply(context, args);
      }
      whenDone();
      throttling = true;
      return result;
    };
};

WPFH.toCurrency = function toCurrency(it){
  return Number(it).toLocaleString('en-US', {style: 'currency', currency: 'USD'});
}

WPFH.fromCurrency = function fromCurrency(it){
  if(typeof(it) == "number") return it;
  return Number(it.toString().replace(/,|\$|\s/ig, ''));
}

WPFH.prefixRemover = function prefixRemover(prefix){
  var prefixRe = new RegExp('^'+prefix);
  return function deprefixed(k){
    return k.replace(prefixRe, '');
  };
};

WPFH.collapseHandler = function collapseHandler(){};

WPFH.makeCollapsibleContainer = function makeCollapsibleContainer(content, options){
  /*
.wpfh-collapsible .collapsible-title{ display:inline-block;  margin: 0 8px;}
.wpfh-collapsible > .wpfh-collapse-icon {display:inline-block; margin: 0 8px;  border:1px solid black; background-color:#CCF; padding:5px;}
.wpfh-collapsible > .wpfh-collapse-body {border: 1px solid black; background:white; padding:5px; margin:5px;}
.wpfh-collapsible.collapsed > .wpfh-collapse-body {height:5px; overflow:hidden;}

    jQuery("body").on("click", ".wpfh-collapse-icon",
    function(evt){
    var el = jQuery(evt.target);
    if(!el.is('.wpfh-collapsible')) el = el.parents('.wpfh-collapsible');
    console.log('toggling', el, el.hasClass('collasped'))
    el.toggleClass('collapsed');
    });
   */
  var col = jQuery("<div class=\"wpfh-collapsible\">"+
                   "<div class=\"wpfh-collapse-icon\"><span class=\"while-collapsed\"> - </span> <span class=\"hide-while-collapsed\"> + </span></div>"+
                   "<h3 class=collapsible-title></h3></div>");
  var body = jQuery("<div class=\"wpfh-collapse-body\">");
  col.append(body);
  content = jQuery(content);
  body.append(content);
  if(options && options.collapsed){ col.addClass("collapsed");}
  if(options && options.title){ jQuery(".collapsible-title",col).html(options.title);}
  return col;
};


// Triggered on init by attr "wp-include"
WPFH._includeCache={};
WPFH.include = function(parent, path, object, cache){
  if(cache === null || cache === undefined){ cache = true; }
  var suc = function(resp){
    console.log('wp_included path: ', path, ' cache: ',cache, ' exists:', (WPFH._includeCache[path] ?'Y':'N'));
    if(cache) WPFH._includeCache[path]=resp;
    parent.html(resp);
    if(object) WPFH.bind(parent, object);
    return new Promise(function(resolve){resolve(resp);});
  };
  if(cache && WPFH._includeCache[path]){
    var html = WPFH._includeCache[path];
    return suc(html);
  }

  var d = Object.assign({path:path}, object);
  return jQuery.ajax({
    url: ajaxurl+"?action=wp_include",
    method: "POST",
    async:true,
    data: d,
    error: function(resp){
      console.log('failed to get admin inv', resp);
    },
    success: suc
  });
};
WPFH.include.doc = "Triggered on init by attr 'wp-include' ";

WPFH.bindOne = function(inp, v, k){

  if (inp.is('[type=radio],[type=checkbox]')){

    inp.attr('checked', inp.val() == v ? true : null);
    inp.prop('checked', inp.val() == v ? true : null);
  }
  else if(inp.is('[type=date]')){
    if(v)try{
      v = v && new Date(v).toISOString() || '';
      v = v.replace(/[\sT].*/,''); // remove time component for date boxes;
    }catch(e){ console.log('Couldnt handle date: ', v); }
    inp.val(v);
  }
  else if (inp.is(':input')){

    if(inp.hasClass('currency')
       || inp.parents('label').hasClass('currency')) inp.val(Number(v).toFixed(2));
    else if(inp.hasClass('number')
           || inp.parents('label').hasClass('number')) inp.val(Number(v));
    else inp.val(v || '');
  }
  else if (inp.is('a') && k == "email"){ inp.attr('href', 'mailto:'+v);}
  else if (inp.is('a')){ inp.attr('href', v || '');}
  else if (inp.is('span,td')){

    if(inp.hasClass('currency'))inp.text(WPFH.toCurrency(v));
    else if(inp.hasClass('decimal'))inp.text(Number(v).toFixed(2));
    else if(inp.hasClass('number')) inp.text(Number(v).toString());
    else if (inp.hasClass('as-html')) inp.html(v || '');
    else if( inp.hasClass('date') || inp.hasClass('datetime') ){
      inp.html((v||'').replace(' 00:00:00',''));
    }
    else inp.text(v || '');
  }
};

WPFH.bind  = function bind(el, o, keyMod){
  var el = jQuery(el);
  jQuery.each(o, function(k, v){

    if(keyMod){k = keyMod(k); }

    if(!k || k.indexOf('$')==0) return true;

    // if should by default hide, use your stylesheet
    if(v !== null && v !== false){ jQuery('.if-'+k, el).show(); }
    else{ jQuery('.if-'+k, el).hide(); }

    var sel = '[name='+k+"],[name='"+k+"[]'],."+k;
    var inps = jQuery(sel, el);
    // console.log(el, o, inps.length, inps);
    inps.each(function(i, inp){
      // if(k == "is_closed") console.log('binding ', inp.outerHTML, k, o[k]);
      // console.log('Binding ', k, v,  inp, o);
      WPFH.bindOne(jQuery(inp), v, k);
    });
  });
};
WPFH.bind.doc = "Take an object and use its keys to fill matching elements"+
  " in the html. Each object key will bind to spans with classes and inputs"+
  " with names matching the keys.  Elts with if-{name} will be hidden or"+
  " shown based on the truthfullness of the associated value";

WPFH.addObjectVal = function (o, k, v){

  v = WPFH.trimAndNullify(v);
  if(WPFH._serializePrefix) k = WPFH._serializePrefix + k;
  if(o[k]){
    if(Array.isArray(o[k])){ o[k].push(v); }
    else{ o[k]= [o[k], v]; }
  }
  else{ o[k] = v; }
};

WPFH.setObjectVals = function setObjectVals(el, o){
  var inps = jQuery(':input[name]', el).each(function(i, inp){
    // console.log('Serializing ', inp[0].outerHTML, o, name, inp.val());
    inp = jQuery(inp);
    var name = inp.attr('name').trim();
    if(inp.is('[type=checkbox]')){
      var val = inp.is(':checked') ? inp.val() : null;
      WPFH.addObjectVal(o, name, val);
    }
    else if(inp.is('[type=radio]')){
      if(inp.is(':checked')){
        // console.log('Serializing ', inp[0].outerHTML, o, name, inp.val());
        WPFH.addObjectVal(o, name, inp.val());
      }
    }
    else{
      WPFH.addObjectVal(o, name, inp.val());
    }
  });
};

WPFH._serializePrefix = null;
WPFH.serialize = WPFH.serializeForm = function(el, prefix){
  var rtn = {}, before = WPFH._serializePrefix;
  el = jQuery(el);
  try{
    WPFH._serializePrefix = prefix;
    WPFH.setObjectVals(el, rtn);
    return rtn;
  }finally{
    WPFH._serializePrefix = before;
  }
};

WPFH.serialize.doc = "Given an element, return an object representing each form value";

WPFH._plus = new RegExp('\\+','g');
WPFH.parseQuery = function(query) {
  if(!query) query = window.location.search.substring(1);
  var obj = {};
  var vars = query.split('&');
  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split('=');
    var k = decodeURIComponent(pair[0]).replace(WPFH._plus, " ");
    var v = decodeURIComponent(pair[1]).replace(WPFH._plus, " ");
    if(obj[k]){
      if (Array.isArray(obj[k])) obj[k].push(v);
      else obj[k]= [obj[k],v];
    }
    else obj[k]=v;
  }
  return obj;
};

WPFH.query = WPFH.parseQuery();

WPFH._initHashQuery = function _initHashQuery(){
  WPFH.hashQuery = {};
  WPFH.hashString = window.location.hash.substring(2);
  if(WPFH.hashString) WPFH.hashQuery = WPFH.parseQuery(WPFH.hashString);
  // console.log('Hash Query: ', WPFH.hashQuery);
}
WPFH._initHashQuery();


WPFH.setHashQuery = function setHashQuery(k, v){
  WPFH._initHashQuery();
  if(v !== null && v !== undefined){
    WPFH.hashQuery[k] = v;
  }else{
    delete(WPFH.hashQuery[k]);
  }

  var arr = [], keys = Object.keys(WPFH.hashQuery) ;
  for(var i=0; i < keys.length ; i++){
    var k = keys[i], v = WPFH.hashQuery[k];
    arr.push(encodeURIComponent(k)+"="+encodeURIComponent(v));
  }
  window.location.hash = '#?'+arr.join('&');
};

var _serializer = function() {
  const seen = [];
  return function (key, value){
    if(key.indexOf('$') == 0 ){
      //console.log('dropping dom elements', key);
      return null;
    }
    else if (typeof value === "object" && value !== null) {
      if (seen.indexOf(value) >= 0 ) {
        //console.log('Removing circular ', key, value);
        return null;
      }
      seen.push(value);
    }
    return value;
  };
};

WPFH.deepClone = function deepClone(o){
  if(!o) o;
  return JSON.parse(JSON.stringify(o, _serializer()));
};



WPFH.templates=[];

WPFH.getTemplate = function getTemplate(name){
  if(!WPFH.templates){
    console.log("Templates not initialized");
    return false;
  }
  var tem = WPFH.templates.filter('.template-'+name+', .'+name+'-template,.template.'+name).first();
  if(!tem){
    console.log('couldnt find ',name,' template in ', WPFH.templates);
    return false;
  }
  var tem = tem.clone();
  tem.removeClass('template template-'+name);
  return tem;
};

WPFH._$dialog = null;
WPFH.dialog = function(message, title, cls, modal){
  if(WPFH._$dialog){
    WPFH._$dialog.dialog('destroy').remove();
    WPFH._$dialog=null;
  }
  var d = WPFH._$dialog = jQuery("<div class='message'></div>");
  d.addClass(cls);
  d.html(message);
  d.dialog({title: title, modal:modal});
  return d;
}
WPFH.errorDialog = function(message, title){
  return WPFH.dialog(message, title || "Please correct these errors: ", "error");
}
WPFH.infoDialog = function(message, title, modal){
  return WPFH.dialog(message, title || "Note: ", "info", modal);
}

WPFH.successDialog = function(message, title){
  return WPFH.dialog(message, title || "Success: ", "info", true);
};

WPFH.collapsePane = function collapsePane(pane){
  console.log('collapsing: ', pane);
  if(!pane.is('.pane')){ pane.parents('.pane'); }
  pane.addClass('collapsed');
  jQuery('.toggle .glyphicon', pane).addClass("glyphicon-circle-arrow-right").
    removeClass("glyphicon-circle-arrow-down");
};

WPFH.togglePane = function togglePane(pane){
  console.log('Toggling: ', pane);
  if(!pane.is('.pane')){ pane.parents('.pane'); }
  pane.toggleClass('collapsed');
  if(pane.hasClass('collapsed')){
    jQuery('.toggle .glyphicon', pane).addClass("glyphicon-circle-arrow-right").
      removeClass("glyphicon-circle-arrow-down");
  }else{
    jQuery('.toggle .glyphicon', pane).addClass("glyphicon-circle-arrow-down").
      removeClass("glyphicon-circle-arrow-right");
  }
};

WPFH.templates=null;
WPFH.initTemplates = function(){
  var t = jQuery('.template');
  t.detach();
  if(WPFH.templates){
    WPFH.templates = WPFH.templates.add(t);
    console.log( 'Added more templates', t, WPFH.templates);
  }else{
    WPFH.templates = t;
    console.log( 'Added templates', t, WPFH.templates);
  }
};

WPFH.baseInit = function baseInit(){
  WPFH.initTemplates();
  jQuery('body').on('click', '.toggle', function(evt){
    var el = jQuery(evt.target);
    if(jQuery('[type=checkbox]', el).length>0) return true;
    var p = el.parents('.pane');
    WPFH.togglePane(p);
  });

  jQuery('[wp-include]').each(function(){
    var parent = jQuery(this);
    var path = parent.attr('wp-include');
    WPFH.include(parent, path);
  });

};

WPFH.resetForm = function resetForm (form){

  jQuery(':input', jQuery(form)).each(function(){
    WPFH.resetField(this);
  });
};

WPFH.resetField = function resetField(field){
  var f = jQuery(field),
      d = f.attr('default') || f.data('default'),
      dfn = eval(f.attr('defaultfn') || f.data('defaultfn')),
      dfnv = dfn && dfn(f);
  if(d){
    f.val(d);
  }else if(dfn){
    f.val(dfn(f));
  }else{
    f.val(null);
  }
};

WPFH.optionMultiSelector = function(field, title, options){
  var o = "<div class='"+field+"-selector'>\n";
  o += "<label><span class=text>"+title+": </span><input name="+field+"-search class=date-search></label>";
  for(var opt,i ; opt=options[i] ; i++){
    o += "<label><input type=checkbox value='"+opt+"'><span class=text>"+opt+"</span></label>\n";
  }
  o += "</div>";
  return jQuery(o);
};

jQuery(WPFH.baseInit);
