<?php # ��������������� �����-������ �� ���������, ������� ��������� ����������

class Pg_Media extends PgUnitAbstract {

	function initAjx()
	{
		return array(
		);
	}
  
	function _rigthList()
	{
		return array(
		);
	}

	function initAcl()
	{
		return array(
			'admin'=>true,
			'owner'=>true,
			'default'=>null
		);
	}
  
	function render()
	{
		global $shape;
		$editMode = $this->hasRight() && core::$inEdit;
		
		if (!$editMode) $shape['jses'] .= '<script type="text/javascript" src="/js/player/jwplayer.js"></script><script type="text/javascript" src="/js/pg_media.js"></script>';

		return '';
	}
}
?>