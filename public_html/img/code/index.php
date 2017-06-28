<?php

 $C_IMAGE_TYPE = 'JPEG';                             //��������� �������: GIF, JPEG, PNG
 $C_WIDTH = 145; //150;                                    //������ �����������
 $C_HEIGHT = 50; //60;                                    //������ �����������
 $C_NUM_GENSIGN = rand(5,6);                                //���������� ��������, ������� ����� �������
 $path_fonts = './fonts/';                          //���� � �������
 session_name('aks');

 session_start();

 $C_FONT_SIZE = intval($C_HEIGHT/(($C_HEIGHT/$C_WIDTH)*8));
 $C_NUM_SIGN = intval(($C_WIDTH*$C_HEIGHT)/150);

 $CODE = array();
 $LETTERS = array('a','b','c','d','e','f','g','h','j','k','m','n','p','q','r','s','t','u','v','w','x','y','z','2','3','4','5','6','7','8','9');
 $FIGURES = array('50','70','90','110','130','150','170','190','210');

 //������� �������
 $src = imagecreatetruecolor($C_WIDTH,$C_HEIGHT);

 //�������� ���
 $fon = imagecolorallocate($src,255,255,255);
 imagefill($src,0,0,$fon);

 //�������� �������
 $FONTS = array();
 $dir=opendir($path_fonts);
 while($fontName = readdir($dir)){
   if($fontName != "." && $fontName != ".."){
     if(strtolower(strrchr($fontName,'.'))=='.ttf') $FONTS[] = $path_fonts.$fontName;
   }
 }
 closedir($dir);

 //���� ���� ������
 if(sizeof($FONTS)>0){
   //�������� ������� ���������
   for($i=0;$i<$C_NUM_SIGN;$i++){
     $h = 1;
     $color = imagecolorallocatealpha($src,rand(0,255),rand(0,255),rand(0,255),100);
     $font = $FONTS[rand(0,sizeof($FONTS)-1)];
     $letter = $LETTERS[rand(0,sizeof($LETTERS)-1)];
     $size = rand($C_FONT_SIZE-2,$C_FONT_SIZE+2);
     $angle = rand(0,60);
     if($h == rand(1,2)) $angle = rand(360,300);
     //�����
     imagettftext($src,$size,$angle,rand($C_WIDTH*0.1,$C_WIDTH-$C_WIDTH*0.1),rand($C_HEIGHT*0.2,$C_HEIGHT),$color,$font,$letter);
   }

   //�������� ��������� ���������
   for($i=0;$i<$C_NUM_GENSIGN;$i++){
     //��������
     $h = 1;
	 //������
     $color = imagecolorallocatealpha($src,$FIGURES[rand(0,sizeof($FIGURES)-1)],$FIGURES[rand(0,sizeof($FIGURES)-1)],$FIGURES[rand(0,sizeof($FIGURES)-1)],rand(10,30));
     $font = $FONTS[rand(0,sizeof($FONTS)-1)];
     $letter = $LETTERS[rand(0,sizeof($LETTERS)-1)];
     //$letter = $confcode[$i];
     $size = rand($C_FONT_SIZE*2.1-1,$C_FONT_SIZE*2.1+1);
	 $x = (empty($x)) ? $C_WIDTH*0.08 : $x + ($C_WIDTH*0.8)/$C_NUM_GENSIGN+rand(0,$C_WIDTH*0.01);
     $y = ($h == rand(1,2)) ? (($C_HEIGHT*1.15*3)/4) + rand(0,$C_HEIGHT*0.02) : (($C_HEIGHT*1.15*3)/4) - rand(0,$C_HEIGHT*0.02);
     $angle = rand(5,20);
     //����������
     $CODE[] = $letter;
     #if($h == rand(0,10)) $letter = strtoupper($letter);
     if($h == rand(1,2)) $angle = rand(355,340);
     //�����
     imagettftext($src,$size,$angle,$x,$y,$color,$font,$letter);
   }

 //���� ��� �������
 }else{
   //�������� �������
   for($x=0;$x<$C_WIDTH;$x++){
     for($i=0;$i<($C_HEIGHT*$C_WIDTH)/1000;$i++){
	   $color = imagecolorallocatealpha($src,$FIGURES[rand(0,sizeof($FIGURES)-1)],$FIGURES[rand(0,sizeof($FIGURES)-1)],$FIGURES[rand(0,sizeof($FIGURES)-1)],rand(10,30));
       imagesetpixel($src,rand(0,$C_WIDTH),rand(0,$C_HEIGHT),$color);
	 }
   }
   unset($x,$y);
   //�������� ��������� ���������
   for($i=0;$i<$C_NUM_GENSIGN;$i++){
     //��������
     $h = 1;
	 //������
     $color = imagecolorallocatealpha($src,$FIGURES[rand(0,sizeof($FIGURES)-1)],$FIGURES[rand(0,sizeof($FIGURES)-1)],$FIGURES[rand(0,sizeof($FIGURES)-1)],rand(10,30));
//     $letter = $LETTERS[rand(0,sizeof($LETTERS)-1)];
     $letter = $confcode[$i];
	 $x = (empty($x)) ? $C_WIDTH*0.08 : $x + ($C_WIDTH*0.8)/$C_NUM_GENSIGN+rand(0,$C_WIDTH*0.01);
     $y = ($h == rand(1,2)) ? (($C_HEIGHT*1)/4) + rand(0,$C_HEIGHT*0.1) : (($C_HEIGHT*1)/4) - rand(0,$C_HEIGHT*0.1);
     //����������
     $CODE[] = $letter;
     if($h == rand(0,10)) $letter = strtoupper($letter);
     //�����
     imagestring($src,5,$x,$y,$letter,$color);
   }
 }

 //�������� ���
 $_SESSION['securityCode'] = implode('',$CODE);
 // ������
 if($C_IMAGE_TYPE=='PNG'){
   header ("Content-type: image/png");
   imagepng($src);
 }elseif($C_IMAGE_TYPE=='JPEG'){
   header ("Content-type: image/jpeg");
   imagejpeg($src);
 }else{
   header ("Content-type: image/gif");
   imagegif($src);
 }
 imagedestroy($src);
?>