<?php

class M_users extends CI_Model
{
	private $_table = 'tbl_user';

	public function list()
	{
		return $this->db->get($this->_table)->result();
	}

	public function detail($id)
	{
		return $this->db->get_where($this->_table, array('tbl_user_id' => $id));
	}

	public function save()
	{
		$isactive = $this->input->post('isuser');
		$post = $this->input->post();
		$this->value = $post['username'];
		$this->name = $post['name'];
		$this->password = password_hash($post['password'], PASSWORD_BCRYPT);
		$this->level = $post['level'];
        if (isset($isactive)) {
            $this->isactive = 'Y';
        } else {
            $this->isactive = 'N';
        }
		$this->db->insert($this->_table, $this);
	}

	public function update()
	{
		$isactive = $this->input->post('isuser');
		$post = $this->input->post();
		$this->value = $post['username'];
		$this->name = $post['name'];
		if (!empty($post['password'])) {
			$this->password = password_hash($post['password'], PASSWORD_BCRYPT);
		}
		$this->level = $post['level'];
        if (isset($isactive)) {
            $this->isactive = 'Y';
        } else {
            $this->isactive = 'N';
        }
        $this->updated = date('Y-m-d H:i:s');
        $where = array('tbl_user_id' => $post['id_user']);
		$this->db->where($where);
		$this->db->update($this->_table, $this);
	}

	public function delete($id)
	{
		return $this->db->delete($this->_table, array('tbl_user_id' => $id));
	}
}