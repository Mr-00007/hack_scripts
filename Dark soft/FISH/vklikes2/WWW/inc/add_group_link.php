<?
if(isset($_SESSION['access_token'])){
?>
<h3>Заказ подписчиков для группы</h3>
<?   
   if($_POST['for_one']){
   $for_one = intval($_POST['for_one']);
   $kolvo = intval($_POST['kolvo']);
   $name = mysql_real_escape_string(htmlspecialchars($_POST['name']));
   $url = mysql_real_escape_string(htmlspecialchars($_POST['url']));
   $balans = $for_one*$kolvo;
   if(strlen($name)<51 and $name!=NULL){
   if(validateURL(substr($url,0,200))!=false){
   if($kolvo>=5){
   if($for_one>=2){
	       if($user_row["likes"]>=$balans and $balans>=10){
		       $user = $user_row["id"];
			   $link_id = $url;
			   $link_id = str_replace("https://vk.com/","",$link_id);
			   $link_id = str_replace("http://vk.com/","",$link_id);
			   $link_id = str_replace("club","",$link_id);
			   $link_id = str_replace("public","",$link_id);
			   $req = file_get_contents("https://api.vk.com/method/groups.getById?group_id=".$link_id);
			   $data = json_decode($req, true);
			   if($data["response"][0]["is_closed"]==0){
		       mysql_query("INSERT INTO tb_ads (user,balans,name,link,link_id,type,for_one) VALUES ('$user','$balans','$name','$url','$link_id','group','$for_one')");
			   mysql_query("UPDATE tb_members SET likes = likes - $balans WHERE id = '$user'");
			   echo "<div class='w_ok'><div class='wmsg'>Ваш заказ успешно принят! :)</div></div>";
			   }else{
			   echo "<div class='w_warning'><div class='wmsg'>Группа/страница закрыта/частная либо не существует</div></div>";
			   }
		   }else{
		      echo "<div class='w_warning'><div class='wmsg'>Минимальный заказ 10 лайков, либо у Вас недостаточно лайков на балансе</div></div>";
		   }
		   }else{
		   echo "<div class='w_warning'><div class='wmsg'>Минимальная цена подписчика 2 лайка</div></div>";
		   }
		   }else{
		   echo "<div class='w_warning'><div class='wmsg'>Минимум 5 подписчиков</div></div>";
		   }}else{
		   echo "<div class='w_warning'><div class='wmsg'>Неверная ссылка</div></div>";
		   }}else{
		   echo "<div class='w_warning'><div class='wmsg'>Название не заполнено либо превышает 50 символов</div></div>";
		   }
   }
   ?>
   <script type="text/javascript">
function getZakaz(frm)
{
    frm.summa.value = parseInt(parseInt(frm.for_one.value)*parseInt(frm.kolvo.value));
}
</script>
<table width="100%" cellspacing="0" class="table">		
<tbody>
<form action="" method="post" onChange="getZakaz(this.form)">
<tr style="background-color:#FFFFFF;"><td align="right">Название</td><td><input type="text" name="name" size="45" maxlength="50"></td><td>Будет отображаться в списке</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Ссылка</td><td><input type="text" name="url" size="45" maxlength="200"></td><td>Ссылка на группу, страницу</td></tr>

<tr style="background-color:#FFFFFF;"><td align="right">Ваша оплата за 1 подписчика</td><td><input  onKeyDown="getZakaz(this.form)" onKeyUp="getZakaz(this.form)" type="text" name="for_one" size="6" maxlength="3" value="2" ></td><td>Сколько платить юзеру(минимум 2♥)</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Кол-во подписчиков</td><td><input onKeyDown="getZakaz(this.form)" onKeyUp="getZakaz(this.form)" type="text" name="kolvo" size="6" maxlength="6" value="5"></td><td>Сколько накрутить юзеров(минимум 5)</td></tr>
<tr style="background-color:#FFFFFF;"><td align="right">Лайков</td><td><input type="text" size="10" maxlength="6" name="summa" readonly="" value="10"></td><td>Стоимость Вашего заказа</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Подтвердите</td><td colspan="2"><input type="submit" value="Готово" style="font-family:Tahoma; font-size:11px;"></td></tr>
</form>
</tbody>
</table>   
   <?
}else{
echo "<h3>Авторизируйтесь</h3><div class='w_warning'><div class='wmsg'>Пройдите авторизацию для доступа к данной странице</div></div>";
}
?>