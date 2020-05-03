<?php

class M_product extends CI_Model
{
	private $_table = 'tbl_barang';

	public function list()
	{
		$this->db->select('tbl_barang.tbl_barang_id,
							tbl_barang.value,
							tbl_barang.name,
							tbl_barang.jenis_id,
							tbl_jenis_logistik.value as code_type,
							tbl_jenis_logistik.name as type,
							tbl_kategori.value as code_category,
							tbl_kategori.name as category,
							tbl_barang.createdby,
							tbl_barang.keterangan,
							tbl_barang.isactive,
							tbl_barang.qtyavailable,
							tbl_barang.qtyentered,
							tbl_barang.unitprice,
							tbl_barang.budget');
		$this->db->from($this->_table);
		$this->db->join('tbl_jenis_logistik', 'tbl_jenis_logistik.tbl_jenis_id = '.$this->_table.'.jenis_id', 'Left');
		$this->db->join('tbl_kategori', 'tbl_kategori.tbl_kategori_id = '.$this->_table.'.kategori_id', 'Left');
		$query = $this->db->get()->result();
		return $query;
	}

	public function save()
	{
		$isactive = $this->input->post('isproduct');
		$post = $this->input->post();
		$type_id = $post['typelog'];
		$this->value = $post['code'];
		$this->name = $post['name'];		
		$this->keterangan = $post['desc'];	
		$this->jenis_id = $type_id;	
		$this->kategori_id = $post['category'];	
        if (isset($isactive)) {
            $this->isactive = 'Y';
        } else {
            $this->isactive = 'N';
        }
        if ($type_id != 2) {
        	$this->qtyentered = $post['qty'];
        	$this->unitprice = changeFormat($post['unitprice']);
        } else {
        	$this->qtyentered = 0;
        	$this->unitprice = 0;
        }
        $this->budget = changeFormat($post['budget']);
		$this->db->insert($this->_table, $this);
	}

	public function update($data, $where)
	{
		$this->db->where($where);
		return $this->db->update($this->_table, $data);
	}

	public function detail($id)
	{
		return $this->db->get_where($this->_table, array('tbl_barang_id' => $id));
	}

	public function delete($id)
	{
		return $this->db->delete($this->_table, array('tbl_barang_id' => $id));
	}

	public function generateCode() {		
		$firstCode = "PS"; //karakter depan kodenya
		$lastCode = ""; //kode awal
        $sql = $this->db->query("SELECT MAX(RIGHT(value,4)) AS maxcode 
        						FROM ".$this->_table);
        
        if( $sql->num_rows() > 0 ) {
            foreach($sql->result() as $value) {
                $intCode = ((int)$value->maxcode) + 1;
                $lastCode = sprintf("%04s", $intCode);
            }

        } else {
            $lastCode = "0001";
        }
        return $firstCode.$lastCode;
   }

   	public function listProduct()
	{
		$uri = $this->uri->segment(1);
		$this->db->select('tbl_barang_id,
							value,
							name,
							isactive');
		$this->db->from($this->_table);
		$this->db->where('isactive', 'Y');
		if ($uri == 'productin') {
			$this->db->where('jenis_id !=', 2);
		}
		$this->db->order_by('value', 'ASC');
		$query = $this->db->get()->result();
		return $query;
	}

	public function totalTypeBudget($id_product, $type_id)
	{
		if ($id_product == null) {
			$sql = $this->db->query("SELECT sum(b.budget) as typebudget_total
							FROM tbl_barang b
							WHERE b.jenis_id = $type_id");
		} else {
			$sql = $this->db->query("SELECT sum(b.budget) as typebudget_total
							FROM tbl_barang b
							WHERE b.jenis_id = $type_id
							AND b.tbl_barang_id != $id_product");
		}
		return $sql->row();
	}

	public function totalBudgetOut($type_id,$year)
	{
		if ($type_id != 2) {
			$sql = $this->db->query("SELECT sum(bm.amount) as budget_total
								FROM tbl_barang b
								LEFT JOIN tbl_barangmasuk bm ON b.tbl_barang_id = bm.tbl_barang_id
								WHERE b.jenis_id = $type_id
								AND YEAR(bm.datetrx) = $year");
		} else {
			$sql = $this->db->query("SELECT sum(bk.amount) as budget_total
								FROM tbl_barang b
								LEFT JOIN tbl_barangkeluar bk ON b.tbl_barang_id = bk.tbl_barang_id
								WHERE b.jenis_id = $type_id
								AND YEAR(bk.datetrx) = $year");
		}
		
		return $sql->row();
	}
	
	public function productBudgetOut($id_product,$type_id,$year)
	{
		if ($type_id != 2) { //budget keluar dari barang masuk
			$sql = $this->db->query("SELECT sum(bm.amount) as budget_out
								FROM tbl_barang b
								LEFT JOIN tbl_barangmasuk bm ON b.tbl_barang_id = bm.tbl_barang_id
								WHERE b.tbl_barang_id = $id_product
								AND b.jenis_id = $type_id
								AND bm.status = 'CO'
								AND YEAR(bm.datetrx) = $year");
		} else { //budget keluar dari barang keluar
			$sql = $this->db->query("SELECT sum(bk.amount) as budget_out
								FROM tbl_barang b
								LEFT JOIN tbl_barangkeluar bk ON b.tbl_barang_id = bk.tbl_barang_id
								WHERE b.tbl_barang_id = $id_product
								AND b.jenis_id = $type_id
								AND bk.status = 'CO'
								AND YEAR(bk.datetrx) = $year");
		}
		
		return $sql->row();
	}

	public function allBudgetOut($year)
	{
		$sql = $this->db->query("SELECT
								SUM(xp.total) AS budget_total
								FROM ((SELECT
									bm.amount AS total
								FROM tbl_barang b
								LEFT JOIN tbl_barangmasuk bm ON b.tbl_barang_id = bm.tbl_barang_id
								WHERE YEAR(bm.datetrx) = $year)
								UNION ALL
								(SELECT
									bk.amount AS total
								FROM tbl_barang b
								LEFT JOIN tbl_barangkeluar bk ON b.tbl_barang_id = bk.tbl_barang_id AND b.jenis_id = 2
								WHERE b.jenis_id = 2
								AND YEAR(bk.datetrx) = $year)) xp");
		return $sql->row();
	}
}