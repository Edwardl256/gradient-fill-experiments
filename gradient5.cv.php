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
	let bboxs = [];
	let areas = [];
	let contours;
	let hierarchy;
	let img = new Image();
	let x = 0;
	let y = 0;
	let pick = 0;
	let temp;
	
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
		//ctx.drawImage(img,0,0);
		let src = cv.imread(img, cv.IMREAD_UNCHANGED);
		let dst = new cv.Mat();
		let low = new cv.Mat(src.rows, src.cols, src.type(), [255,255,255,0]);
		let high = new cv.Mat(src.rows, src.cols, src.type(), [255,255,255,255]);
		contours = new cv.MatVector();
		hierarchy = new cv.Mat();
		cv.inRange(src,low,high,dst);
		cv.findContours(dst, contours, hierarchy, cv.RETR_TREE, cv.CHAIN_APPROX_NONE);
		console.log(contours);
		console.log(hierarchy);
		pick = 0;
		let biggest = 0;
		for(let i = 0; i < contours.size(); i++)
		{
			bboxs.push(cv.boundingRect(contours.get(i)));
			areas.push(cv.contourArea(contours.get(i),false));
			if(areas[i] > biggest)
			{
				pick = i;
				biggest = areas[i];
			}
		}
		let cclr = new cv.Scalar(0,255,255);
		cv.drawContours(dst, contours, pick, cclr, 1, 8, hierarchy, 100);
		console.log(bboxs);
		console.log(areas);
		console.log(pick);
		cv.imshow("pic", dst);
		temp = ctx.getImageData(bboxs[pick].x,bboxs[pick].y,bboxs[pick].width,bboxs[pick].height);
		ctx.strokeStyle = "red";
		for(let i = 0; i < bboxs.length; i++)
		{
			ctx.beginPath();
			ctx.rect(bboxs[i].x,bboxs[i].y,bboxs[i].width,bboxs[i].height);
			ctx.stroke();
		}
		ctx.strokeStyle = "green";
		ctx.beginPath();
		ctx.rect(bboxs[pick].x,bboxs[pick].y,bboxs[pick].width,bboxs[pick].height);
		ctx.stroke();
		// Let us fill!
		y = bboxs[pick].y;
		requestAnimationFrame(makeGrad);
	}
	function makeGrad()
	{
		for(let x = bboxs[pick].x; x < bboxs[pick].x+bboxs[pick].width; x++)
		{
			let dist = cv.pointPolygonTest(contours.get(pick), new cv.Point(x,y), true);
			if(dist > 0)
			{
				//console.log(Math.round(dist*2));
				ctx.fillStyle = "rgb(0," + Math.round(dist*2) + ",255)";
				ctx.fillRect(x,y,1,1);
			}
		}
		if(y < bboxs[pick].y+bboxs[pick].height)
		{
			y++;
			requestAnimationFrame(makeGrad);
		}
		else
		{
			ctx.putImageData(temp,bboxs[pick].x+bboxs[pick].width,bboxs[pick].y);
			ctx.strokeStyle = "red";
			for(let i = 0; i < bboxs.length; i++)
			{
				ctx.beginPath();
				ctx.rect(bboxs[i].x,bboxs[i].y,bboxs[i].width,bboxs[i].height);
				ctx.stroke();
			}
			ctx.strokeStyle = "green";
			ctx.beginPath();
			ctx.rect(bboxs[pick].x,bboxs[pick].y,bboxs[pick].width,bboxs[pick].height);
			ctx.stroke();
		}
	}


openCvReady();
</script>
</body>
</html>
