<?php
function intval_32bits($value)
{
    $value = ($value & 0xFFFFFFFF);
    if ($value & 0x80000000) $value = -((~$value & 0xFFFFFFFF) + 1);
    return $value;
}
function NewArray($number)
{
    $arr = [];
    for ($i=0; $i < $number; $i++) { 
        $arr[$i] = 0;
    }
    return $arr;
}

class AriaAlgorithm {
    public $KRK;
    public $S1;
    public $S2;
    public $X1;
    public $X2;

    public $TS1;
    public $TS2;
    public $TX1;
    public $TX2;

    public $keySize = 0;
    public $numberOfRounds = 0;
    public $masterKey = null;
    public $encRoundKeys = null;
    public $decRoundKeys = null;

    public function __construct($keySize) {
        $this->KRK = [
            [intval_32bits(0x517cc1b7), intval_32bits(0x27220a94), intval_32bits(0xfe13abe8), intval_32bits(0xfa9a6ee0)],
            [intval_32bits(0x6db14acc), intval_32bits(0x9e21c820), intval_32bits(0xff28b1d5), intval_32bits(0xef5de2b0)],
            [intval_32bits(0xdb92371d), intval_32bits(0x2126e970), intval_32bits(0x03249775), intval_32bits(0x04e8c90e)]
        ];
        $this->S1 = NewArray(0x100);
        $this->S2 = NewArray(0x100);
        $this->X1 = NewArray(0x100);
        $this->X2 = NewArray(0x100);
        $this->TS1 = NewArray(0x100);
        $this->TS2 = NewArray(0x100);
        $this->TX1 = NewArray(0x100);
        $this->TX2 = NewArray(0x100);

        $this->Initialize();
        $this->SetKeySize($keySize);
    }
    public function Initialize()
    {
        $exp =  NewArray(0x100);
        $log = NewArray(256);
        $exp[0] = 1;
        for ($i = 1; $i < 256; $i++)
        {
            $j = ($exp[$i - 1] << 1) ^ $exp[$i - 1];
            if (($j & 0x100) != 0) $j ^= 0x11b;
            $exp[$i] = $j;
        }
        for ($i = 1; $i < 255; $i++)
        {
            $log[$exp[$i]] = $i;
        }

        $A = [
            [1, 0, 0, 0, 1, 1, 1, 1],
            [1, 1, 0, 0, 0, 1, 1, 1],
            [1, 1, 1, 0, 0, 0, 1, 1],
            [1, 1, 1, 1, 0, 0, 0, 1],
            [1, 1, 1, 1, 1, 0, 0, 0],
            [0, 1, 1, 1, 1, 1, 0, 0],
            [0, 0, 1, 1, 1, 1, 1, 0],
            [0, 0, 0, 1, 1, 1, 1, 1]
        ];
        $B = [
            [0, 1, 0, 1, 1, 1, 1, 0],
            [0, 0, 1, 1, 1, 1, 0, 1],
            [1, 1, 0, 1, 0, 1, 1, 1],
            [1, 0, 0, 1, 1, 1, 0, 1],
            [0, 0, 1, 0, 1, 1, 0, 0],
            [1, 0, 0, 0, 0, 0, 0, 1],
            [0, 1, 0, 1, 1, 1, 0, 1],
            [1, 1, 0, 1, 0, 0, 1, 1]
        ];
        for ($i = 0; $i < 256; $i++)
        {
            $t = 0; $p=0;
            if ($i == 0)
            {
                $p = 0;
            }
            else
            {
                $p =$exp[255 - $log[$i]];
            }
            for ($j = 0; $j < 8; $j++)
            {
                $s = 0;
                for ($k = 0; $k < 8; $k++)
                {
                    if ((($p >> ((7 - $k) & 0x1f)) & 1) != 0)
                    {
                        $s ^= $A[$k][$j];
                    }
                }
                $t = ($t << 1) ^ $s;
            }
            $t ^= 0x63;
            $this->S1[$i] = $t;
            $this->X1[$t] = $i;
        }
        for ($i = 0; $i < 256; $i++)
        {
            $t = 0;
            $p;
            if ($i == 0)
            {
                $p = 0;
            }
            else
            {
                $p = $exp[(0xf7 * $log[$i]) % 0xff];
            }
            for ($j = 0; $j < 8; $j++)
            {
                $s = 0;
                for ($k = 0; $k < 8; $k++)
                {
                    if ((($p >> ($k & 0x1f)) & 1) != 0)
                    {
                        $s ^= $B[7 - $j][$k];
                    }
                }
                $t = ($t << 1) ^ $s;
            }
            $t ^= 0xe2;
            $this->S2[$i] = $t;
            $this->X2[$t] = $i;
        }
        
        for ($i = 0; $i < 256; $i++)
        {
            $this->TS1[$i] = intval_32bits(0x00010101 * ($this->S1[$i] & 0xff));
            $this->TS2[$i] = intval_32bits(0x01000101 * ($this->S2[$i] & 0xff));
            $this->TX1[$i] = intval_32bits(0x01010001 * ($this->X1[$i] & 0xff));
            $this->TX2[$i] = intval_32bits(0x01010100 * ($this->X2[$i] & 0xff));
        }
    }
    public function SetKeySize($keySize)
    {
        $this->Reset();
        if ($keySize != 0x80 && $keySize != 0xc0 && $keySize != 0x100)
        {
            throw new Exception("keySize=" + $keySize);
        }
        $this->keySize = $keySize;
        switch ($keySize)
        {
            case 0x80:
                $this->numberOfRounds = 12;
                break;

            case 0xc0:
                $this->numberOfRounds = 14;
                break;

            case 0x100:
                $this->numberOfRounds = 16;
                break;
        }
    }
    public function Reset()
    {
        $this->keySize = 0;
        $this->numberOfRounds = 0;
        $this->masterKey = null;
        $this->encRoundKeys = null;
        $this->decRoundKeys = null;
    }

    public function  GetKeySize()
    {
        return $this->keySize;
    }

    public function SetKey($masterKey)
    {
        if (sizeof($masterKey) * 8 < $this->keySize)
        {
            throw new Exception("masterKey size=" + sizeof($masterKey));
        }
        $this->decRoundKeys = null;
        $this->encRoundKeys = null;
        $this->masterKey = $masterKey;
    }

    public function SetupEncRoundKeys()
    {
        if ($this->keySize == 0)
        {
            throw new Exception("keySize");
        }
        if ($this->masterKey == null)
        {
            throw new Exception("masterKey");
        }
        if ($this->encRoundKeys == null)
        {
            $t = 4 * ($this->numberOfRounds + 1);
            if ($t < 0)
            {
                throw new Exception();
            }
            $this->encRoundKeys = NewArray($t);
        }
        $this->decRoundKeys = null;
        $this->DoEncKeySetup($this->masterKey, $this->encRoundKeys, $this->keySize);
    }

    public function SetupDecRoundKeys()
    {
        if ($this->keySize == 0)
        {
            throw new Exception("keySize");
        }
        if ($this->encRoundKeys == null)
        {
            if ($this->masterKey == null)
            {
                throw new Exception("masterKey");
            }
            else
            {
                $this->SetupEncRoundKeys();
            }
        }
        $this->decRoundKeys = $this->encRoundKeys;
        
        $this->DoDecKeySetup($this->masterKey, $this->decRoundKeys, $this->keySize);
    }
    public function SetupRoundKeys()
    {
        $this->SetupDecRoundKeys();
    }

    public function ToInt($b0, $b1, $b2, $b3)
    {
        return (($b0 & 0xff) << 0x18 ^ ($b1 & 0xff) << 0x10 ^ ($b2 & 0xff) << 8 ^ $b3 & 0xff);
    }

    public function ToByteArray($i, $b, $offset)
    {
        $b[$offset] = (($i & 0xFFFFFFFF) >> 0x18);
        $b[$offset + 1] = (($i & 0xFFFFFFFF) >> 0x10);
        $b[$offset + 2] = (($i & 0xFFFFFFFF) >> 8);
        $b[$offset + 3] = (($i & 0xFFFFFFFF));
    }

    public function Badc($t)
    {
        return intval_32bits((intval((int)($t << 8) & 0xff00ff00)) ^ (intval((($t & 0xFFFFFFFF) >> 8) & 0x00ff00ff)));
    }
    public function Cdab($t)
    {
        return intval_32bits(intval((int)(($t << 0x10) & 0xffff0000)) ^ (intval((int)(($t & 0xFFFFFFFF) >> 0x10) & 0x0000ffff)));
    }
    public function Dcba($t)
    {
        return intval_32bits(intval((int)(($t & 0x000000ff) << 0x18))) ^ intval_32bits(intval((int)(($t & 0x0000ff00) << 8))) ^ intval_32bits(intval((int)((($t & 0x00ff0000) & 0xFFFFFFFF) >> 8))) ^ intval_32bits(intval((int)((($t & 0xff000000) & 0xFFFFFFFF) >> 0x18)));
    }

    public function DoCrypt($i, $ioffset, $rk, $nr, &$o, $ooffset)
    {
        $j = 0;
        $t0 = $this->ToInt($i[0 + $ioffset], $i[1 + $ioffset], $i[2 + $ioffset], $i[3 + $ioffset]);
        $t1 = $this->ToInt($i[4 + $ioffset], $i[5 + $ioffset], $i[6 + $ioffset], $i[7 + $ioffset]);
        $t2 = $this->ToInt($i[8 + $ioffset], $i[9 + $ioffset], $i[10 + $ioffset], $i[11 + $ioffset]);
        $t3 = $this->ToInt($i[12 + $ioffset], $i[13 + $ioffset], $i[14 + $ioffset], $i[15 + $ioffset]);

        for ($r = 1; $r < $nr / 2; $r++)
        {
            $t0 ^= $rk[$j++];
            $t1 ^= $rk[$j++];
            $t2 ^= $rk[$j++];
            $t3 ^= $rk[$j++];

            $t0 = $this->TS1[(($t0 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t0 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t0 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t0 & 0xff];
            $t1 = $this->TS1[(($t1 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t1 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t1 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t1 & 0xff];
            $t2 = $this->TS1[(($t2 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t2 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t2 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t2 & 0xff];
            $t3 = $this->TS1[(($t3 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t3 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t3 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t3 & 0xff];

            $t1 ^= $t2;
            $t2 ^= $t3;
            $t0 ^= $t1;
            $t3 ^= $t1;
            $t2 ^= $t0;
            $t1 ^= $t2;

            $t1 = $this->Badc($t1);
            $t2 = $this->Cdab($t2);
            $t3 = $this->Dcba($t3);

            $t1 ^= $t2;
            $t2 ^= $t3;
            $t0 ^= $t1;
            $t3 ^= $t1;
            $t2 ^= $t0;
            $t1 ^= $t2;

            $t0 ^= $rk[$j++];
            $t1 ^= $rk[$j++];
            $t2 ^= $rk[$j++];
            $t3 ^= $rk[$j++];

            $t0 = $this->TX1[(($t0 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TX2[(($t0 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TS1[(($t0 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TS2[$t0 & 0xff];
            $t1 = $this->TX1[(($t1 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TX2[(($t1 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TS1[(($t1 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TS2[$t1 & 0xff];
            $t2 = $this->TX1[(($t2 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TX2[(($t2 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TS1[(($t2 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TS2[$t2 & 0xff];
            $t3 = $this->TX1[(($t3 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TX2[(($t3 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TS1[(($t3 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TS2[$t3 & 0xff];

            $t1 ^= $t2;
            $t2 ^= $t3;
            $t0 ^= $t1;
            $t3 ^= $t1;
            $t2 ^= $t0;
            $t1 ^= $t2;

            $t3 = $this->Badc($t3);
            $t0 = $this->Cdab($t0);
            $t1 = $this->Dcba($t1);

            $t1 ^= $t2;
            $t2 ^= $t3;
            $t0 ^= $t1;
            $t3 ^= $t1;
            $t2 ^= $t0;
            $t1 ^= $t2;
        }

       $t0 ^= $rk[$j++];
       $t1 ^= $rk[$j++];
       $t2 ^= $rk[$j++];
       $t3 ^= $rk[$j++];

       $t0 = $this->TS1[(($t0 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t0 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t0 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t0 & 0xff];
       $t1 = $this->TS1[(($t1 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t1 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t1 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t1 & 0xff];
       $t2 = $this->TS1[(($t2 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t2 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t2 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t2 & 0xff];
       $t3 = $this->TS1[(($t3 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t3 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t3 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t3 & 0xff];

       $t1 ^= $t2;
       $t2 ^= $t3;
       $t0 ^= $t1;
       $t3 ^= $t1;
       $t2 ^= $t0;
       $t1 ^= $t2;

       $t1 = $this->Badc($t1);
       $t2 = $this->Cdab($t2);
       $t3 = $this->Dcba($t3);

       $t1 ^= $t2;
       $t2 ^= $t3;
       $t0 ^= $t1;
       $t3 ^= $t1;
       $t2 ^= $t0;
       $t1 ^= $t2;

       $t0 ^= $rk[$j++];
       $t1 ^= $rk[$j++];
       $t2 ^= $rk[$j++];
       $t3 ^= $rk[$j++];

       $o[0 + $ooffset] = unpack("C*",pack("L",($this->X1[0xff & (($t0 & 0xFFFFFFFF) >> 0x18)] ^ (($rk[$j] & 0xFFFFFFFF) >> 0x18))))[1];
       $o[1 + $ooffset] = unpack("C*",pack("L",($this->X2[0xff & (($t0 & 0xFFFFFFFF) >> 0x10)] ^ (($rk[$j] & 0xFFFFFFFF) >> 0x10))))[1];
       $o[2 + $ooffset] = unpack("C*",pack("L",($this->S1[0xff & (($t0 & 0xFFFFFFFF) >> 8)] ^ (($rk[$j] & 0xFFFFFFFF) >> 8))))[1];
       $o[3 + $ooffset] = unpack("C*",pack("L",($this->S2[0xff & ($t0)] ^ ($rk[$j]))))[1];
       $o[4 + $ooffset] = unpack("C*",pack("L",($this->X1[0xff & (($t1 & 0xFFFFFFFF) >> 0x18)] ^ (($rk[$j + 1]  & 0xFFFFFFFF) >> 0x18))))[1];
       $o[5 + $ooffset] = unpack("C*",pack("L",($this->X2[0xff & (($t1 & 0xFFFFFFFF) >> 0x10)] ^ (($rk[$j + 1]  & 0xFFFFFFFF) >> 0x10))))[1];
       $o[6 + $ooffset] = unpack("C*",pack("L",($this->S1[0xff & (($t1 & 0xFFFFFFFF) >> 8)] ^ (($rk[$j + 1]  & 0xFFFFFFFF) >> 8))))[1];
       $o[7 + $ooffset] = unpack("C*",pack("L",($this->S2[0xff & ($t1)] ^ ($rk[$j + 1]))))[1];
       $o[8 + $ooffset] = unpack("C*",pack("L",($this->X1[0xff & (($t2 & 0xFFFFFFFF) >> 0x18)] ^ (($rk[$j + 2]  & 0xFFFFFFFF) >> 0x18))))[1];
       $o[9 + $ooffset] = unpack("C*",pack("L",($this->X2[0xff & (($t2 & 0xFFFFFFFF) >> 0x10)] ^ (($rk[$j + 2]  & 0xFFFFFFFF) >> 0x10))))[1];
       $o[10 + $ooffset] = unpack("C*",pack("L",($this->S1[0xff & (($t2 & 0xFFFFFFFF) >> 8)] ^ (($rk[$j + 2]  & 0xFFFFFFFF) >> 8))))[1];
       $o[11 + $ooffset] = unpack("C*",pack("L",($this->S2[0xff & ($t2)] ^ ($rk[$j + 2]))))[1];
       $o[12 + $ooffset] = unpack("C*",pack("L",($this->X1[0xff & (($t3 & 0xFFFFFFFF) >> 0x18)] ^ (($rk[$j + 3]  & 0xFFFFFFFF) >> 0x18))))[1];
       $o[13 + $ooffset] = unpack("C*",pack("L",($this->X2[0xff & (($t3 & 0xFFFFFFFF) >> 0x10)] ^ (($rk[$j + 3]  & 0xFFFFFFFF) >> 0x10))))[1];
       $o[14 + $ooffset] = unpack("C*",pack("L",($this->S1[0xff & (($t3 & 0xFFFFFFFF) >> 8)] ^ (($rk[$j + 3]  & 0xFFFFFFFF) >> 8))))[1];
       $o[15 + $ooffset] = unpack("C*",pack("L",($this->S2[0xff & ($t3)] ^ ($rk[$j + 3]))))[1];
    }

    public function DoEncKeySetup($mk, &$rk, $keyBits)
    {
        
        $t0 = 0;
        $t1=0;
        $t2=0;
        $t3=0;
        $q=0;
        $j = 0;

        $w0 = NewArray(4);
        $w1 = NewArray(4);
        $w2 = NewArray(4);
        $w3 = NewArray(4);

        
        

        $w0[0] = $this->ToInt($mk[0], $mk[1], $mk[2], $mk[3]);
        $w0[1] = $this->ToInt($mk[4], $mk[5], $mk[6], $mk[7]);
        $w0[2] = $this->ToInt($mk[8], $mk[9], $mk[10], $mk[11]);
        $w0[3] = $this->ToInt($mk[12], $mk[13], $mk[14], $mk[15]);

        $q = ($keyBits - 0x80) / 0x40;

        $t0 = $w0[0] ^ $this->KRK[$q][0];
        $t1 = $w0[1] ^ $this->KRK[$q][1];
        $t2 = $w0[2] ^ $this->KRK[$q][2];
        $t3 = $w0[3] ^ $this->KRK[$q][3];

        $t0 = $this->TS1[(($t0 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[( ($t0 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[( ($t0 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t0 & 0xff];
        $t1 = $this->TS1[(($t1 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[( ($t1 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[( ($t1 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t1 & 0xff];
        $t2 = $this->TS1[(($t2 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t2 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t2 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t2 & 0xff];
        $t3 = $this->TS1[(($t3 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t3 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t3 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t3 & 0xff];

        $t1 ^= $t2;
        $t2 ^= $t3;
        $t0 ^= $t1;
        $t3 ^= $t1;
        $t2 ^= $t0;
        $t1 ^= $t2;

        $t1 = $this->Badc($t1);
        $t2 = $this->Cdab($t2);
        $t3 = $this->Dcba($t3);

        $t1 ^= $t2;
        $t2 ^= $t3;
        $t0 ^= $t1;
        $t3 ^= $t1;
        $t2 ^= $t0;
        $t1 ^= $t2;

        if ($keyBits > 128)
        {
            $w1[0] = $this->ToInt($mk[16], $mk[17], $mk[18], $mk[19]);
            $w1[1] = $this->ToInt($mk[20], $mk[21], $mk[22], $mk[23]);

            if ($keyBits > 192)
            {
                $w1[2] = $this->ToInt($mk[24], $mk[25], $mk[26], $mk[27]);
                $w1[3] = $this->ToInt($mk[28], $mk[29], $mk[30], $mk[31]);
            }
            else
            {
                $w1[2] = $w1[3] = 0;
            }
        }
        else
        {
            $w1[0] = $w1[1] = $w1[2] = $w1[3] = 0;
        }

        $w1[0] ^= $t0;
        $w1[1] ^= $t1;
        $w1[2] ^= $t2;
        $w1[3] ^= $t3;

        $t0 = $w1[0];
        $t1 = $w1[1];
        $t2 = $w1[2];
        $t3 = $w1[3];

        $q = ($q == 2) ? 0 : ($q + 1);

        $t0 ^= $this->KRK[$q][0];
        $t1 ^= $this->KRK[$q][1];
        $t2 ^= $this->KRK[$q][2];
        $t3 ^= $this->KRK[$q][3];

        $t0 = $this->TX1[(($t0 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TX2[(($t0 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TS1[(($t0 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TS2[$t0 & 0xff];
        $t1 = $this->TX1[(($t1 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TX2[(($t1 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TS1[(($t1 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TS2[$t1 & 0xff];
        $t2 = $this->TX1[(($t2 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TX2[(($t2 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TS1[(($t2 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TS2[$t2 & 0xff];
        $t3 = $this->TX1[(($t3 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TX2[(($t3 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TS1[(($t3 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TS2[$t3 & 0xff];

        $t1 ^= $t2;
        $t2 ^= $t3;
        $t0 ^= $t1;
        $t3 ^= $t1;
        $t2 ^= $t0;
        $t1 ^= $t2;

        $t3 = $this->Badc($t3);
        $t0 = $this->Cdab($t0);
        $t1 = $this->Dcba($t1);

        $t1 ^= $t2;
        $t2 ^= $t3;
        $t0 ^= $t1;
        $t3 ^= $t1;
        $t2 ^= $t0;
        $t1 ^= $t2;

        $t0 ^= $w0[0];
        $t1 ^= $w0[1];
        $t2 ^= $w0[2];
        $t3 ^= $w0[3];

        $w2[0] = $t0;
        $w2[1] = $t1;
        $w2[2] = $t2;
        $w2[3] = $t3;

        $q = ($q == 2) ? 0 : ($q + 1);

        $t0 ^= $this->KRK[$q][0];
        $t1 ^= $this->KRK[$q][1];
        $t2 ^= $this->KRK[$q][2];
        $t3 ^= $this->KRK[$q][3];

        $t0 = $this->TS1[(($t0 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t0 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t0 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t0 & 0xff];
        $t1 = $this->TS1[(($t1 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t1 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t1 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t1 & 0xff];
        $t2 = $this->TS1[(($t2 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t2 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t2 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t2 & 0xff];
        $t3 = $this->TS1[(($t3 & 0xFFFFFFFF) >> 0x18) & 0xff] ^ $this->TS2[(($t3 & 0xFFFFFFFF) >> 0x10) & 0xff] ^ $this->TX1[(($t3 & 0xFFFFFFFF) >> 8) & 0xff] ^ $this->TX2[$t3 & 0xff];

        $t1 ^= $t2;
        $t2 ^= $t3;
        $t0 ^= $t1;
        $t3 ^= $t1;
        $t2 ^= $t0;
        $t1 ^= $t2;

        $t1 = $this->Badc($t1);
        $t2 = $this->Cdab($t2);
        $t3 = $this->Dcba($t3);

        $t1 ^= $t2;
        $t2 ^= $t3;
        $t0 ^= $t1;
        $t3 ^= $t1;
        $t2 ^= $t0;
        $t1 ^= $t2;

        $w3[0] = $t0 ^ $w1[0];
        $w3[1] = $t1 ^ $w1[1];
        $w3[2] = $t2 ^ $w1[2];
        $w3[3] = $t3 ^ $w1[3];

        $this->Gsrk($w0, $w1, 19, $rk, $j); $j += 4;
        $this->Gsrk($w1, $w2, 19, $rk, $j); $j += 4;
        $this->Gsrk($w2, $w3, 19, $rk, $j); $j += 4;
        $this->Gsrk($w3, $w0, 19, $rk, $j); $j += 4;
        $this->Gsrk($w0, $w1, 31, $rk, $j); $j += 4;
        $this->Gsrk($w1, $w2, 31, $rk, $j); $j += 4;
        $this->Gsrk($w2, $w3, 31, $rk, $j); $j += 4;
        $this->Gsrk($w3, $w0, 31, $rk, $j); $j += 4;
        $this->Gsrk($w0, $w1, 67, $rk, $j); $j += 4;
        $this->Gsrk($w1, $w2, 67, $rk, $j); $j += 4;
        $this->Gsrk($w2, $w3, 67, $rk, $j); $j += 4;
        $this->Gsrk($w3, $w0, 67, $rk, $j); $j += 4;
        $this->Gsrk($w0, $w1, 97, $rk, $j); $j += 4;

        if ($keyBits > 0x80)
        {
            $this->Gsrk($w1, $w2, 97, $rk, $j); $j += 4;
            $this->Gsrk($w2, $w3, 97, $rk, $j); $j += 4;
        }
        if ($keyBits > 0xc0)
        {
            $this->Gsrk($w3, $w0, 97, $rk, $j); $j += 4;
            $this->Gsrk($w0, $w1, 109, $rk, $j);
        }
    }

    public function Gsrk($x, $y, $rot, &$rk, $offset)
    {
        $q = 4 - (int)($rot / 32);
        $r = (int)($rot % 32);
        $s = 32 - $r;
        $rk[$offset] = intval_32bits($x[0] ^ (int)($y[$q % 4] & 0xFFFFFFFF) >> $r ^ $y[($q + 3) % 4] << $s);
        $rk[$offset + 1] = intval_32bits($x[1] ^ (int)(($y[($q + 1) % 4] & 0xFFFFFFFF) >> $r) ^ $y[$q % 4] << $s);
        $rk[$offset + 2] = intval_32bits($x[2] ^ (int)(($y[($q + 2) % 4] & 0xFFFFFFFF) >> $r) ^ $y[($q + 1) % 4] << $s);
        $rk[$offset + 3] = intval_32bits(($x[3] ^ (int)(($y[($q + 3) % 4] & 0xFFFFFFFF) >> $r)) ^ ($y[($q + 2) % 4] << $s));

    }

    public function Encrypt($i, $ioffset, &$o, $ooffset)
    {
        if ($this->keySize == 0)
        {
            throw new Exception("keySize");
        }
        if ($this->encRoundKeys == null)
        {
            if ($this->masterKey == null)
            {
                throw new Exception("masterKey");
            }
            else
            {
                $this->SetupEncRoundKeys();
            }
        }
        $this->DoCrypt($i, $ioffset, $this->encRoundKeys, $this->numberOfRounds, $o, $ooffset);
    }

    public function Encrypt1($i, $ioffset)
    {
        $o = NewArray(16);
        $this->Encrypt($i, $ioffset, $o, 0);
        return $o;
    }

    public function Decrypt($i, $ioffset, &$o, $ooffset)
    {
        if ($this->keySize == 0)
        {
            throw new Exception("keySize");
        }
        if ($this->decRoundKeys == null)
        {
            if ($this->masterKey == null)
            {
                throw new Exception("masterKey");
            }
            else
            {
                $this->SetupDecRoundKeys();
            }
        }
        $this->DoCrypt($i, $ioffset, $this->decRoundKeys, $this->numberOfRounds, $o, $ooffset);
    }

    public function Decrypt1($i, $ioffset)
    {
        $o = NewArray(16);
        $this->Decrypt($i, $ioffset, $o, 0);
        return $o;
    }

    public function DoDecKeySetup($mk, &$rk, $keyBits)
    {
        $a = 0;
        $z=0;
        $t = NewArray(4);

        $z = 32 + $keyBits / 8;
        $this->SwapBlocks($rk, 0, $z);
        $a += 4; $z -= 4;

        for (; $a < $z; $a += 4, $z -= 4)
        {
            $this->SwapAndDiffuse($rk, $a, $z, $t);
        }
        $this->Diff($rk, $a, $t, 0);

        $rk[$a] = $t[0];
        $rk[$a + 1] = $t[1];
        $rk[$a + 2] = $t[2];
        $rk[$a + 3] = $t[3];
    }

    public function Diff($i, $offset1, &$o, $offset2)
    {
        $t0 =0; $t1 = 0; $t2 = 0; $t3 = 0;

        $t0 = $this->M($i[$offset1]);
        $t1 = $this->M($i[$offset1 + 1]);
        $t2 = $this->M($i[$offset1 + 2]);
        $t3 = $this->M($i[$offset1 + 3]);
        $t1 ^= $t2;
        $t2 ^= $t3;
        $t0 ^= $t1;
        $t3 ^= $t1;
        $t2 ^= $t0;
        $t1 ^= $t2;

        $t1 = $this->Badc($t1);
        $t2 = $this->Cdab($t2);
        $t3 = $this->Dcba($t3);

        $t1 ^= $t2;
        $t2 ^= $t3;
        $t0 ^= $t1;
        $t3 ^= $t1;
        $t2 ^= $t0;
        $t1 ^= $t2;

        $o[$offset2] = $t0;
        $o[$offset2 + 1] = $t1;
        $o[$offset2 + 2] = $t2;
        $o[$offset2 + 3] = $t3;
    }

    public function M($t)
    {
        return intval_32bits((int)(0x00010101 * ((($t & 0xFFFFFFFF) >> 0x18) & 0xff)) ^ (int)(0x01000101 * ((($t & 0xFFFFFFFF) >> 0x10) & 0xff)) ^ (int)(0x01010001 * ((($t & 0xFFFFFFFF) >> 8) & 0xff)) ^ 0x01010100 * ($t & 0xff));
    }

    public function SwapAndDiffuse(&$arr, $offset1, $offset2, $tmp)
    {
        $this->Diff($arr, $offset1, $tmp, 0); 
        $this->Diff($arr, $offset2, $arr, $offset1);
        $arr[$offset2] = $tmp[0];
        $arr[$offset2 + 1] = $tmp[1];
        $arr[$offset2 + 2] = $tmp[2];
        $arr[$offset2 + 3] = $tmp[3];
    }

    public function SwapBlocks(&$arr, $offset1, $offset2)
    {
        $t = 0;

        for ($i = 0; $i < 4; $i++)
        {
            $t = $arr[$offset1 + $i];
            $arr[$offset1 + $i] = $arr[$offset2 + $i];
            $arr[$offset2 + $i] = $t;
        }
    }
}

class AriaProvider {
    public $aria;
    public $keySize;
    public $masterKey = null;

    public $plainTextBlock = null;
    public $cipherTextBlock = null;

    public function __construct() {
        $this->keySize = 128;
        $this->aria = new AriaAlgorithm($this->keySize);
    }

    public function SetDefaultMasterKey()
    {
        if ($this->masterKey == null)
        {
            $length = ($this->keySize == 128) ? 16 : (($this->keySize == 192) ? 24 : 32);
            $this->masterKey = NewArray($length);
            for ($i = 0; $i < $length; $i++)
            {
                $this->masterKey[$i] = $i;
            }
        }
    }

    public function Encrypt($inputText)
    {
        $this->SetDefaultMasterKey();
        $this->aria->SetKey($this->masterKey);
        $this->aria->SetupRoundKeys();

        $this->EncryptDivisionBlock($inputText);
        $this->Encrypt1();
    }

    public function Encrypt1()
    {
        $this->cipherTextBlock = NewArray(sizeof($this->plainTextBlock));
        for ($i = 0; $i < sizeof($this->plainTextBlock); $i++)
        {
            $this->cipherTextBlock[$i] = NewArray(16);
            $this->aria->Encrypt($this->plainTextBlock[$i], 0, $this->cipherTextBlock[$i], 0);
        }
    }

    public function Decrypt2()
    {
        $this->plainTextBlock = NewArray(sizeof($this->cipherTextBlock));
        for ($i = 0; $i < sizeof($this->cipherTextBlock); $i++)
        {
            $this->plainTextBlock[$i] = NewArray(16);
            $this->aria->Decrypt($this->cipherTextBlock[$i], 0, $this->plainTextBlock[$i], 0);
        }
    }
    public function Decrypt($inputText)
    {
        $this->SetDefaultMasterKey();
        $this->aria->SetKey($this->masterKey);
        $this->aria->SetupRoundKeys();

        $this->DecryptDivisionBlock($inputText);
        $this->Decrypt2();
    }

    public function Decrypt1($inputArray)
    {
        $this->SetDefaultMasterKey();
        $this->aria->SetKey($this->masterKey);
        $this->aria->SetupRoundKeys();

        $this->DecryptDivisionBlock($inputArray);
        $this->Decrypt2();
    }

    public function ConvertToByteArrayFromString($str)
    {
        $temp = NewArray(strlen($str));
        for ($i=0; $i < sizeof($temp); $i++) { 
            $temp[$i] = ord(substr($str,$i,1));
        }
        if (sizeof($temp) % 16 != 0)
        {
            $temp2 = NewArray((intval(sizeof($temp) / 16) + 1) * 16);
            $this->BlockCopy($temp, 0, $temp2, 0, sizeof($temp));
            return $temp2;
        }
        else
        {
            return $temp;
        }
    }

    public function EncryptedConvertToByteArrayFromString($str)
    {
        $temp = NewArray(strlen($str) / 2);
        for ($i = 0; $i < sizeof($temp); $i++)
        {
            $hex = substr($str,$i * 2, 2); 
            $temp[$i] = hexdec($hex);
        }
        if (sizeof($temp) % 16 != 0)
        {
            $temp2 = NewArray((intval(sizeof($temp) / 16) + 1) * 16);
            $this->BlockCopy($temp, 0, $temp2, 0, sizeof($temp));
            return $temp2;
        }
        else
        {
            return $temp;
        }
    }

    public function EncryptDivisionBlock($plainText)
    {
        $temp = $this->ConvertToByteArrayFromString($plainText);
        $index = (int)((sizeof($temp) - 1) / 16 + 1);
        $this->plainTextBlock = NewArray($index);
        for ($i = 0, $j = 0; $j < $index; $i += 16, $j++)
        {
            $this->plainTextBlock[$j] = NewArray(16);
            $this->BlockCopy($temp, $i, $this->plainTextBlock[$j], 0, 16);
        }
    }

    public function DecryptDivisionBlock($cipherText)
    {
        $temp = $this->EncryptedConvertToByteArrayFromString($cipherText);
        $index = intval((sizeof($temp) - 1) / 16 + 1);
        $this->cipherTextBlock = NewArray($index);
        for ($i = 0, $j = 0; $j < $index; $i += 16, $j++)
        {
            $this->cipherTextBlock[$j] = NewArray(16);
            $this->BlockCopy($temp, $i, $this->cipherTextBlock[$j], 0, 16);
        }
    }

    public function DecryptDivisionBlock1($cipherByteArray)
    {
        $index = intval((sizeof($cipherByteArray) - 1) / 16 + 1);
        $this->cipherTextBlock = NewArray($index);
        for ($i = 0, $j = 0; $j < $index; $i += 16, $j++)
        {
            $this->cipherTextBlock[$j] = NewArray(16);
            $this->BlockCopy($cipherByteArray, $i, $this->cipherTextBlock[$j], 0, 16);
        }
    }

    public function EncryptToString($inputString)
    {
        if ($inputString == '')
        {
            return $inputString;
        }
        else
        {
            $this->Encrypt($inputString);
            
            $sb = '';
            for ($i = 0; $i < sizeof($this->cipherTextBlock); $i++)
            {
                for ($j=0; $j < sizeof($this->cipherTextBlock[$i]); $j++) { 
                    $sb .= strtoupper(strlen(dechex($this->cipherTextBlock[$i][$j]))>1?dechex($this->cipherTextBlock[$i][$j]):'0'.dechex($this->cipherTextBlock[$i][$j]));
                }
            }
            return $sb;
        }
    }

    public function EncryptToByteArray($inputString)
    {
        if ($inputString== '')
        {
            return null;
        }
        else
        {
            $this->Encrypt($inputString);

            $retValue = NewArray(sizeof($this->cipherTextBlock) * 16);
            for ($i = 0, $j = 0; $i < sizeof($this->cipherTextBlock); $i++, $j += 16)
            {
                $this->BlockCopy($this->cipherTextBlock[$i], 0, $retValue, $j, 16);
            }
            return $retValue;
        }
    }

    public function DecryptFromString($inputString)
    {
        $dest = NewArray(strlen($inputString));
        $resuft = '';
        if ($inputString == '')
        {
            return $inputString;
        }
        else
        {
            $this->Decrypt($inputString);
            for ($i = 0, $j = 0; $i < sizeof($this->plainTextBlock); $i++, $j += 16)
            {
                $this->BlockCopyDec($this->plainTextBlock[$i], 0, $dest, $j, 16);
            }
            for ($i=0; $i < sizeof($dest); $i++) { 
                $resuft .= chr($dest[$i]);
            }
            return $resuft;
        }
    }

    public function DecryptFromByteArray($inputByteArray)
    {
        $dest = NewArray(sizeof($inputByteArray));
        $resuft = '';
        if (empty($inputByteArray) || sizeof($inputByteArray) == 0)
        {
            return null;
        }
        else
        {
            $this->Decrypt1($inputByteArray);

            for ($i = 0, $j = 0; $i < sizeof($this->plainTextBlock); $i++, $j += 16)
            {
                $this->BlockCopyDec($this->plainTextBlock[$i], 0, $dest, $j, 16);
            }
            for ($n = 0; $n< sizeof($dest); $n++) {
                if ($dest[$n] != 0) {
                    $resuft += chr($dest[$n]);
                }
            }
            return $resuft;
        }
    }
    public function BlockCopyDec($src,$srcOffset,&$dst,$dstOffset,$count){
        for ($i = $srcOffset; $i < $count ; $i++) {
            $dst[$dstOffset + $i] = $src[$i];
        }
        return $dst;
    }
    public function BlockCopy($src,$srcOffset,&$dst,$dstOffset,$count){
        for ($i = $dstOffset; $i < $count ; $i++) {
            $dst[$i] = $src[$srcOffset + $i];
        }
        return $dst;
    }
}

$AriaProvider = new AriaProvider();
if($_GET['mk']){
    echo $AriaProvider->EncryptToString($_GET['mk']);
}
echo '<br>-------<br>';
if($_GET['mamk']){
    echo $AriaProvider->DecryptFromString('562002A078F874CA39D9EC8F7B7CA59227BF1E394E81FBF03AC343F5068780C5');
}
