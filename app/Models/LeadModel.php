<?php

namespace App\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $table            = 'leads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'email', 'phone', 'status', 'date_added', 'last_updated'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setDates'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = ['setUpdateDate'];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    protected function setDates(array $data)
    {
        $data['data']['date_added'] = date('Y-m-d H:i:s');
        $data['data']['last_updated'] = date('Y-m-d H:i:s');
        return $data;
    }

    protected function setUpdateDate(array $data)
    {
        $data['data']['last_updated'] = date('Y-m-d H:i:s');
        return $data;
    }

    // Add custom validation method
    public function validateData($data, $id = null)
    {
        $rules = [
            'name' => [
                'rules' => 'required|min_length[3]|max_length[255]',
                'errors' => [
                    'required' => 'Name is required',
                    'min_length' => 'Name must be at least 3 characters long',
                    'max_length' => 'Name cannot exceed 255 characters'
                ]
            ],
            'phone' => [
                'rules' => 'required|min_length[10]|max_length[15]',
                'errors' => [
                    'required' => 'Phone number is required',
                    'min_length' => 'Phone number must be at least 10 characters long',
                    'max_length' => 'Phone number cannot exceed 15 characters'
                ]
            ],
            'status' => [
                'rules' => 'required|in_list[New,In Progress,Closed]',
                'errors' => [
                    'required' => 'Status is required',
                    'in_list' => 'Status must be one of: New, In Progress, Closed'
                ]
            ]
        ];

        // Email validation with unique check
        $emailRule = 'required|valid_email';
        if ($id === null) {
            // For new records
            $emailRule .= '|is_unique[leads.email]';
        } else {
            // For existing records, exclude current ID
            $emailRule .= "|is_unique[leads.email,id,$id]";
        }

        $rules['email'] = [
            'rules' => $emailRule,
            'errors' => [
                'required' => 'Email is required',
                'valid_email' => 'Please enter a valid email address',
                'is_unique' => 'This email address is already in use'
            ]
        ];

        $validation = \Config\Services::validation();
        $validation->setRules($rules);

        return $validation->run($data);
    }
}
