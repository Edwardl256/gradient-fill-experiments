<?php
	$getdoc = file("https://docs.opencv.org/",FILE_SKIP_EMPTY_LINES);
	$found = false;
	$l = 0;
	while($found == false && $l < count($getdoc))
	{
		if(stripos($getdoc[$l],"doxygen") !== false)
			$found = $l;
		$l++;
	}
	if($found !== false)
	{
		$getver = $getdoc[$found+2];
		$getver = substr($getver,stripos($getver,"./")+1);
		$getver = substr($getver,0,stripos($getver,"\""));
	}

	unset($getdoc);
?>
<html>
<body>
<canvas id="pic" style="position:absolute;left:0px;top:0px;" width="4096px" height="2304px"></canvas>
<script src="https://docs.opencv.org<?php echo $getver ?>/opencv.js" type="text/javascript"></script>
<script>
	let canvas;
	let ctx;
	let bbox;
	let contours;
	let hierarchy;
	let img = new Image();
	let x = 0;
	let y = 0;
	let x0 = 0;
	let y0 = 0;
	let temp;
	let temp2;
	
	function openCvReady() {
		cv['onRuntimeInitialized']=()=>{
			console.log("READY");
			canvas = document.getElementById("pic");
			ctx = canvas.getContext("2d");
			img.src = "raritydashserendipity.png";
		};
	}
	img.onload = function()
	{
		ctx.drawImage(img,0,0);
		canvas.onclick = function(e)
		{
			x0 = e.layerX;
			y0 = e.layerY;
			let color0 = ctx.getImageData(x0,y0,1,1).data;
			let src = cv.matFromImageData(ctx.getImageData(0,0,canvas.width,canvas.height));
			let dst = new cv.Mat();
			let distTrans = new cv.Mat();
			let low = new cv.Mat(src.rows, src.cols, src.type(), [color0[0],color0[1],color0[2],0]);
			let high = new cv.Mat(src.rows, src.cols, src.type(), [color0[0],color0[1],color0[2],255]);
			contours = new cv.MatVector();
			hierarchy = new cv.Mat();
			cv.inRange(src,low,high,dst);
			cv.findContours(dst, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_NONE);
			console.log(contours);
			for(let i = 0; i < contours.size(); i++)
			{	// Find the contour that the mouse was in.
				if(cv.pointPolygonTest(contours.get(i), new cv.Point(x0,y0), true) > 0)
				{
					bbox = cv.boundingRect(contours.get(i));
					i = contours.size();
				}
			}
			console.log(bbox);
			temp2 = ctx.getImageData(bbox.x,bbox.y,bbox.width,bbox.height);
			temp = ctx.getImageData(bbox.x,bbox.y,bbox.width,bbox.height);
			contours.delete();
			hierarchy.delete();
			src.delete();
			dst.delete();
			low.delete();
			high.delete();
			console.log(temp);
			let borderskip = true;
			for(i = 0; i < temp.data.length; i+=4)
			{	// For some reason I just wanted everything to be exact...
				// Also need to setup a border hack, otherwise the contour seems to continue to trace the colors beyond.
				if( Math.floor((i / 4) / bbox.width) == 0 || Math.floor((i / 4) / bbox.width) == bbox.height-1)
				{
					borderskip = true;
				}
				else
				{
					if((i / 4) % bbox.width == 0 || (i / 4) % bbox.width == bbox.width-1)
					{
						borderskip = true;
					}
					else
					{
						borderskip = false;
					}
				}
				if(temp.data[i] == color0[0] && temp.data[i+1] == color0[1] && temp.data[i+2] == color0[2] && borderskip == false)
				{
					temp.data[i]   = 255;
					temp.data[i+1] = 255;
					temp.data[i+2] = 255;
					temp.data[i+3] = 255;
				}
				else
				{
					temp.data[i]   = 0;
					temp.data[i+1] = 0;
					temp.data[i+2] = 0;
					temp.data[i+3] = 255;
				}
			}
			ctx.putImageData(temp,bbox.x,bbox.y);
			src = cv.matFromImageData(temp);
			cv.cvtColor(src,src, cv.COLOR_RGBA2GRAY, 0);
			cv.threshold(src,src, 0, 255, cv.THRESH_BINARY+cv.THRESH_OTSU);
			cv.distanceTransform(src, distTrans, cv.DIST_L2, cv.DIST_MASK_PRECISE);
			cv.normalize(distTrans, distTrans, 255, 0, cv.NORM_INF);
//			distTrans.convertTo(distTrans, cv.CV_8U, scale, shift);
			console.log(distTrans);
//			cv.cvtColor(distTrans, distTrans, cv.COLOR_GRAY2RGBA);
/*
	Something was not quite right here...
	I followed examples and for some reason the values weren't making sense.
	It said to add "scale" and "shift" but just gave an abstract of what they represent.
	I'm sure I'm missing steps but ended up writing the numbers directly
	into the imagedata.

	distTrans.data (and data8S) seemed like binary when drawn directly,
	and using a conversion (I think up above) seemed to make the data
	four times bigger than width x height x 4 channels.
*/
			for(i = 0; i < temp.data.length; i+=4)
			{
				if(Math.round(distTrans.data32F[Math.floor(i/4)])>0)
				{
					temp.data[i]   = 0;
					temp.data[i+1] = Math.round(distTrans.data32F[Math.floor(i/4)]);
					temp.data[i+2] = 255;
					temp.data[i+3] = 255;
				}
				else
				{
					temp.data[i]   = temp2.data[i];
					temp.data[i+1] = temp2.data[i+1];
					temp.data[i+2] = temp2.data[i+2];
					temp.data[i+3] = temp2.data[i+3];
				}
			}
			ctx.putImageData(temp,bbox.x,bbox.y);
			src.delete();
			distTrans.delete();
		}
	}

openCvReady();
</script>
</body>
</html>
