function ProgressbarMapControl(a,b){this.map_=a;this.width_=b}ProgressbarMapControl.prototype=new GControl(!0,!1); ProgressbarMapControl.prototype.initialize=function(){var a=document.createElement("div");a.innerHTML="<div style='position:absolute;width:100%;border:5px;text-align:center;vertical-align:bottom;' id='geo_progress_text'></div><div style='background-color:#CCCCCC;height:100%;' id='geo_progress'></div>";a.id="geo_progress_container";a.style.display="none";a.style.width=this.width_+"px";a.style.fontSize="0.8em";a.style.height="1.3em";a.style.border="1px solid #555";a.style.backgroundColor="white";a.style.textAlign= "left";this.map_.getContainer().appendChild(a);return a};ProgressbarMapControl.prototype.getDefaultPosition=function(){return new GControlPosition(G_ANCHOR_TOP_LEFT,new GSize(this.map_.getContainer().offsetWidth/2-this.width_/2,this.map_.getContainer().offsetHeight/2-5))}; function ProgressbarControl(a,b){this.options_=b==null?{}:b;this.width_=this.options_.width==null?176:this.options_.width;this.loadstring_=this.options_.loadstring==null?"Loading...":this.options_.loadstring;this.control_=new ProgressbarMapControl(a,this.width_);this.map_=a;this.map_.addControl(this.control_);this.div_=document.getElementById("geo_progress");this.text_=document.getElementById("geo_progress_text");this.container_=document.getElementById("geo_progress_container");this.current_=this.operations_= 0}ProgressbarControl.prototype.start=function(a){this.div_.style.width="0%";this.operations_=a||0;this.current_=0;this.text_.style.color="#111";this.text_.innerHTML=this.loadstring_;this.container_.style.display="block"};ProgressbarControl.prototype.updateLoader=function(a){this.current_+=a;if(this.current_>0)a=Math.ceil(this.current_/this.operations_*100),a>100&&(a=100),this.div_.style.width=a+"%",this.text_.innerHTML=this.current_+" / "+this.operations_}; ProgressbarControl.prototype.remove=function(){this.container_.style.display="none"};