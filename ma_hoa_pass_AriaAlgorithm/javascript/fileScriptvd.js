class AriaAlgorithm {
	constructor (keysize) {
        this.KRK = new Array(
        new Int32Array([0x517cc1b7,0x27220a94,0xfe13abe8,0xfa9a6ee0]),
        new Int32Array([0x6db14acc,0x9e21c820,0xff28b1d5,0xef5de2b0]),
        new Int32Array([0xdb92371d,0x2126e970,0x03249775,0x04e8c90e])
        );

        this.S1 = new Uint8Array(0x100);
        this.S2 = new Uint8Array(0x100);
        this.X1 = new Uint8Array(0x100);
        this.X2 = new Uint8Array(0x100);
        
        this.TS1 = new Int32Array(0x100);
        this.TS2 = new Int32Array(0x100);
        this.TX1 = new Int32Array(0x100);
        this.TX2 = new Int32Array(0x100);
        
        this.keySizeAriaAlgorithm = keysize;
        this.numberOfRounds = 0;
        this.masterKeyAriaAlgorithm = null;
        this.encRoundKeys = null;
        this.decRoundKeys = null;
		this.Initialize();
		this.SetKeySize(this.keySizeAriaAlgorithm)
    }
    Initialize(){
	   var exp = new Int32Array(256);
    	var log = new Int32Array(256);
    	exp[0] = 1;
    	 for (let i = 1; i < 256; i++) {
    	   let j = (exp[i-1] << 1) ^ exp[i-1];
    	   if ((j & 0x100) != 0) {
    		 j ^= 0x11b;
    	   }
    	   exp [i] = j;
    	 }
    	 for (let i = 1; i < 255; i++) {
    	   log[exp[i]] = i;
    	 }
    	 var A = new Array(
    	   [1, 0, 0, 0, 1, 1, 1, 1],
    	   [1, 1, 0, 0, 0, 1, 1, 1],
    	   [1, 1, 1, 0, 0, 0, 1, 1],
    	   [1, 1, 1, 1, 0, 0, 0, 1],
    	   [1, 1, 1, 1, 1, 0, 0, 0],
    	   [0, 1, 1, 1, 1, 1, 0, 0],
    	   [0, 0, 1, 1, 1, 1, 1, 0],
    	   [0, 0, 0, 1, 1, 1, 1, 1],
    	 );
    	 var B = new Array(
    	   [0, 1, 0, 1, 1, 1, 1, 0],
    	   [0, 0, 1, 1, 1, 1, 0, 1],
    	   [1, 1, 0, 1, 0, 1, 1, 1],
    	   [1, 0, 0, 1, 1, 1, 0, 1],
    	   [0, 0, 1, 0, 1, 1, 0, 0],
    	   [1, 0, 0, 0, 0, 0, 0, 1],
    	   [0, 1, 0, 1, 1, 1, 0, 1],
    	   [1, 1, 0, 1, 0, 0, 1, 1],
    	 );
    	 for (let i = 0; i < 256; i++) {
    	   let t = 0;let p;
    		if (i == 0)
    		{
    			p = 0;
    		}
    		else
    		{
    			p = exp[255 - log[i]];
    		}
    		for (let j = 0; j < 8; j++)
    		{
    			let s = 0;
    			for (let k = 0; k < 8; k++)
    			{
    				if (((p >> ((7 - k) & 0x1f)) & 1) != 0)
    				{
    					s ^= A[k][j];
    				}
    			}
    			t = (t << 1) ^ s;
    		}
    		t ^= 0x63;
    		this.S1[i] = t;
    		this.X1[t] = i;
    	  }
    	  for (let i = 0; i < 256; i++)
    	  {
    		  let t = 0;
    		  let p;
    		  if (i == 0)
    		  {
    			  p = 0;
    		  }
    		  else
    		  {
    			  p = exp[(0xf7 * log[i]) % 0xff];
    		  }
    		  for (let j = 0; j < 8; j++)
    		  {
    			  let s = 0;
    			  for (let k = 0; k < 8; k++)
    			  {
    				  if (((p >> (k & 0x1f)) & 1) != 0)
    				  {
    					  s ^= B[7 - j][k];
    				  }
    			  }
    			  t = (t << 1) ^ s;
    		  }
    		  t ^= 0xe2;
    		  this.S2[i] = t;
    		  this.X2[t] = i;
    	  }
        	for (let i = 0; i < 256; i++)
        	{
    	       this.TS1[i] = 0x00010101 * (this.S1[i] & 0xff);
    	       this.TS2[i] = 0x01000101 * (this.S2[i] & 0xff);
    	       this.TX1[i] = 0x01010001 * (this.X1[i] & 0xff);
    	       this.TX2[i] = 0x01010100 * (this.X2[i] & 0xff);
    	  }
    }
	Reset() {
		this.keySizeAriaAlgorithm = 0;
		this.numberOfRounds = 0;
		this.masterKeyAriaAlgorithm = null;
		this.encRoundKeys = null;
		this.decRoundKeys = null;
	}
	SetKeySize(keysize) {
		this.Reset();
		if (keysize != 0x80 && keysize != 0xc0 && keysize != 0x100)
		{
		   alert("this.keySize=" + keysize);
		}
		this.keySizeAriaAlgorithm = keysize;
		switch (this.keySizeAriaAlgorithm) {
		case 0x80:
		  this.numberOfRounds = 12;
		  break;
		case 0xc0:
		  this.numberOfRounds = 14;
		  break;
		case 0x100:
		  this.numberOfRounds = 16;
		  break;
		}
	}
	
	GetKeySize(){
		return this.keySizeAriaAlgorithm;
	}
    SetKey(masterkey) {
		if (masterkey.length * 8 < this.keySizeAriaAlgorithm) {
			alert("masterKey size=" + masterKey.length);
		}
		this.decRoundKeys = null;
		this.encRoundKeys = null;
		this.masterKeyAriaAlgorithm = [...masterkey];
	}
	SetupEncRoundKeys() {
		if (this.keySizeAriaAlgorithm == 0) {
			alert("keySize");
		}
		if (this.masterKeyAriaAlgorithm == null) {
			alert("masterKey");
		}
		if (this.encRoundKeys == null)
		{
			let t = 4 * (this.numberOfRounds + 1);
			if (t < 0)
			{
				alert("error");
			}
			this.encRoundKeys = new Int32Array(t);
		}
		this.decRoundKeys = null;
		this.DoEncKeySetup(this.masterKeyAriaAlgorithm, this.encRoundKeys, this.keySizeAriaAlgorithm);
	}
    SetupDecRoundKeys()
    {
        if (this.keySizeAriaAlgorithm == 0)
        {
            alert("keySize");
        }
        if (this.encRoundKeys == null)
        {
            if (this.masterKeyAriaAlgorithm == null)
            {
               alert("masterKey");
            }
            else
            {
                this.SetupEncRoundKeys();
            }
        }
        let temp1 = this.encRoundKeys;
        this.decRoundKeys = [...temp1];
        this.DoDecKeySetup(this.masterKeyAriaAlgorithm, this.decRoundKeys, this.keySizeAriaAlgorithm);
    }
    SetupRoundKeys()
    {
        this.SetupDecRoundKeys();
    }

    DoCrypt(i, ioffset, rk, nr, o, ooffset)
    {
        let j = 0;
        let t0 = this.ToInt(i[0 + ioffset], i[1 + ioffset], i[2 + ioffset], i[3 + ioffset]);
        let t1 = this.ToInt(i[4 + ioffset], i[5 + ioffset], i[6 + ioffset], i[7 + ioffset]);
        let t2 = this.ToInt(i[8 + ioffset], i[9 + ioffset], i[10 + ioffset], i[11 + ioffset]);
        let t3 = this.ToInt(i[12 + ioffset], i[13 + ioffset], i[14 + ioffset], i[15 + ioffset]);

        for (let r = 1; r < nr / 2; r++)
        {
            t0 ^= rk[j++];
            t1 ^= rk[j++];
            t2 ^= rk[j++];
            t3 ^= rk[j++];

            t0 = this.TS1[(parseInt(t0) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t0) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t0) >> 8) & 0xff] ^ this.TX2[t0 & 0xff];
            t1 = this.TS1[(parseInt(t1) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t1) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t1) >> 8) & 0xff] ^ this.TX2[t1 & 0xff];
            t2 = this.TS1[(parseInt(t2) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t2) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t2) >> 8) & 0xff] ^ this.TX2[t2 & 0xff];
            t3 = this.TS1[(parseInt(t3) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t3) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t3) >> 8) & 0xff] ^ this.TX2[t3 & 0xff];

            t1 ^= t2;
            t2 ^= t3;
            t0 ^= t1;
            t3 ^= t1;
            t2 ^= t0;
            t1 ^= t2;

            t1 = this.Badc(t1);
            t2 = this.Cdab(t2);
            t3 = this.Dcba(t3);

            t1 ^= t2;
            t2 ^= t3;
            t0 ^= t1;
            t3 ^= t1;
            t2 ^= t0;
            t1 ^= t2;

            t0 ^= rk[j++];
            t1 ^= rk[j++];
            t2 ^= rk[j++];
            t3 ^= rk[j++];

            t0 = this.TX1[(parseInt(t0) >> 0x18) & 0xff] ^ this.TX2[(parseInt(t0) >> 0x10) & 0xff] ^ this.TS1[(parseInt(t0) >> 8) & 0xff] ^ this.TS2[t0 & 0xff];
            t1 = this.TX1[(parseInt(t1) >> 0x18) & 0xff] ^ this.TX2[(parseInt(t1) >> 0x10) & 0xff] ^ this.TS1[(parseInt(t1) >> 8) & 0xff] ^ this.TS2[t1 & 0xff];
            t2 = this.TX1[(parseInt(t2) >> 0x18) & 0xff] ^ this.TX2[(parseInt(t2) >> 0x10) & 0xff] ^ this.TS1[(parseInt(t2) >> 8) & 0xff] ^ this.TS2[t2 & 0xff];
            t3 = this.TX1[(parseInt(t3) >> 0x18) & 0xff] ^ this.TX2[(parseInt(t3) >> 0x10) & 0xff] ^ this.TS1[(parseInt(t3) >> 8) & 0xff] ^ this.TS2[t3 & 0xff];

            t1 ^= t2;
            t2 ^= t3;
            t0 ^= t1;
            t3 ^= t1;
            t2 ^= t0;
            t1 ^= t2;

            t3 = this.Badc(t3);
            t0 = this.Cdab(t0);
            t1 = this.Dcba(t1);

            t1 ^= t2;
            t2 ^= t3;
            t0 ^= t1;
            t3 ^= t1;
            t2 ^= t0;
            t1 ^= t2;
        }

        t0 ^= rk[j++];
        t1 ^= rk[j++];
        t2 ^= rk[j++];
        t3 ^= rk[j++];

        t0 = this.TS1[(parseInt(t0) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t0) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t0) >> 8) & 0xff] ^ this.TX2[t0 & 0xff];
        t1 = this.TS1[(parseInt(t1) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t1) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t1) >> 8) & 0xff] ^ this.TX2[t1 & 0xff];
        t2 = this.TS1[(parseInt(t2) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t2) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t2) >> 8) & 0xff] ^ this.TX2[t2 & 0xff];
        t3 = this.TS1[(parseInt(t3) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t3) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t3) >> 8) & 0xff] ^ this.TX2[t3 & 0xff];

        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        t1 = this.Badc(t1);
        t2 = this.Cdab(t2);
        t3 = this.Dcba(t3);

        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        t0 ^= rk[j++];
        t1 ^= rk[j++];
        t2 ^= rk[j++];
        t3 ^= rk[j++];

        o[0 + ooffset] = (((this.X1[System.Array.index(((255 & ((t0 >>> 0) >>> 24)) >>> 0), this.X1)] ^ (((rk[System.Array.index(j, rk)]) >>> 0) >>> 24)) >>> 0)) & 255;
        o[1 + ooffset] = (((this.X2[System.Array.index(((255 & ((t0 >>> 0) >>> 16)) >>> 0), this.X2)] ^ (((rk[System.Array.index(j, rk)]) >>> 0) >>> 16)) >>> 0)) & 255;
        o[2 + ooffset] = (((this.S1[System.Array.index(((255 & ((t0 >>> 0) >>> 8)) >>> 0), this.S1)] ^ (((rk[System.Array.index(j, rk)]) >>> 0) >>> 8)) >>> 0)) & 255;
        o[3 + ooffset] = (this.S2[System.Array.index(255 & (t0), this.S2)] ^ (rk[System.Array.index(j, rk)])) & 255;
        o[4 + ooffset] = (((this.X1[System.Array.index(((255 & ((t1 >>> 0) >>> 24)) >>> 0), this.X1)] ^ (((rk[System.Array.index(((j + 1) | 0), rk)]) >>> 0) >>> 24)) >>> 0)) & 255;
        o[5 + ooffset] = (((this.X2[System.Array.index(((255 & ((t1 >>> 0) >>> 16)) >>> 0), this.X2)] ^ (((rk[System.Array.index(((j + 1) | 0), rk)]) >>> 0) >>> 16)) >>> 0)) & 255;
        o[6 + ooffset] = (((this.S1[System.Array.index(((255 & ((t1 >>> 0) >>> 8)) >>> 0), this.S1)] ^ (((rk[System.Array.index(((j + 1) | 0), rk)]) >>> 0) >>> 8)) >>> 0)) & 255;
        o[7 + ooffset] = (this.S2[System.Array.index(255 & (t1), this.S2)] ^ (rk[System.Array.index(((j + 1) | 0), rk)])) & 255;
        o[8 + ooffset] = (((this.X1[System.Array.index(((255 & ((t2 >>> 0) >>> 24)) >>> 0), this.X1)] ^ (((rk[System.Array.index(((j + 2) | 0), rk)]) >>> 0) >>> 24)) >>> 0)) & 255;
        o[9 + ooffset] = (((this.X2[System.Array.index(((255 & ((t2 >>> 0) >>> 16)) >>> 0), this.X2)] ^ (((rk[System.Array.index(((j + 2) | 0), rk)]) >>> 0) >>> 16)) >>> 0)) & 255;
        o[10 + ooffset] = (((this.S1[System.Array.index(((255 & ((t2 >>> 0) >>> 8)) >>> 0), this.S1)] ^ (((rk[System.Array.index(((j + 2) | 0), rk)]) >>> 0) >>> 8)) >>> 0)) & 255;
        o[11 + ooffset] = (this.S2[System.Array.index(255 & (t2), this.S2)] ^ (rk[System.Array.index(((j + 2) | 0), rk)])) & 255;
        o[12 + ooffset] = (((this.X1[System.Array.index(((255 & ((t3 >>> 0) >>> 24)) >>> 0), this.X1)] ^ (((rk[System.Array.index(((j + 3) | 0), rk)]) >>> 0) >>> 24)) >>> 0)) & 255;
        o[13 + ooffset] = (((this.X2[System.Array.index(((255 & ((t3 >>> 0) >>> 16)) >>> 0), this.X2)] ^ (((rk[System.Array.index(((j + 3) | 0), rk)]) >>> 0) >>> 16)) >>> 0)) & 255;
        o[14 + ooffset] = (((this.S1[System.Array.index(((255 & ((t3 >>> 0) >>> 8)) >>> 0), this.S1)] ^ (((rk[System.Array.index(((j + 3) | 0), rk)]) >>> 0) >>> 8)) >>> 0)) & 255;
        o[15 + ooffset] = (this.S2[System.Array.index(255 & (t3), this.S2)] ^ (rk[System.Array.index(((j + 3) | 0), rk)])) & 255;
    }
    DoEncKeySetup(mk,rk,keyBits) {
      	let t0=0,t1= 0,t2=0,t3=0,q=0,j=0;
      	let w0 = new Int32Array(4);
      	let w1 = new Int32Array(4);
      	let w2 = new Int32Array(4);
      	let w3 = new Int32Array(4);

      	w0[0] = this.ToInt(mk[0], mk[1], mk[2], mk[3]);
      	w0[1] = this.ToInt(mk[4], mk[5], mk[6], mk[7]);
      	w0[2] = this.ToInt(mk[8], mk[9], mk[10], mk[11]);
      	w0[3] = this.ToInt(mk[12], mk[13], mk[14], mk[15]);

      	q = (keyBits - 0x80) / 0x40;

      	t0 = w0[0] ^ this.KRK[q][0];
      	t1 = w0[1] ^ this.KRK[q][1];
      	t2 = w0[2] ^ this.KRK[q][2];
      	t3 = w0[3] ^ this.KRK[q][3];

      	t0 = this.TS1[(parseInt(t0) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t0) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t0) >> 8) & 0xff] ^ this.TX2[t0 & 0xff];
      	t1 = this.TS1[(parseInt(t1) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t1) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t1) >> 8) & 0xff] ^ this.TX2[t1 & 0xff];
      	t2 = this.TS1[(parseInt(t2) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t2) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t2) >> 8) & 0xff] ^ this.TX2[t2 & 0xff];
      	t3 = this.TS1[(parseInt(t3) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t3) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t3) >> 8) & 0xff] ^ this.TX2[t3 & 0xff];
      	
        t1 ^= t2; 
        t2 ^= t3; 
        t0 ^= t1; 
        t3 ^= t1; 
        t2 ^= t0; 
        t1 ^= t2;

        t1 = this.Badc(t1);
        t2 = this.Cdab(t2);
        t3 = this.Dcba(t3);

        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        if (keyBits > 128)
        {
            w1[0] = this.ToInt(mk[16], mk[17], mk[18], mk[19]);
            w1[1] = this.ToInt(mk[20], mk[21], mk[22], mk[23]);

            if (keyBits > 192)
            {
                w1[2] = this.ToInt(mk[24], mk[25], mk[26], mk[27]);
                w1[3] = this.ToInt(mk[28], mk[29], mk[30], mk[31]);
            }
            else
            {
                w1[2] = w1[3] = 0;
            }
        }
        else
        {
            w1[0] = w1[1] = w1[2] = w1[3] = 0;
        }

        w1[0] ^= t0;
        w1[1] ^= t1;
        w1[2] ^= t2;
        w1[3] ^= t3;

        t0 = w1[0];
        t1 = w1[1];
        t2 = w1[2];
        t3 = w1[3];

        q = (q == 2) ? 0 : (q + 1);

        t0 ^= this.KRK[q][0];
        t1 ^= this.KRK[q][1];
        t2 ^= this.KRK[q][2];
        t3 ^= this.KRK[q][3];

        t0 = this.TX1[(parseInt(t0) >> 0x18) & 0xff] ^ this.TX2[(parseInt(t0) >> 0x10) & 0xff] ^ this.TS1[(parseInt(t0) >> 8) & 0xff] ^ this.TS2[t0 & 0xff];
        t1 = this.TX1[(parseInt(t1) >> 0x18) & 0xff] ^ this.TX2[(parseInt(t1) >> 0x10) & 0xff] ^ this.TS1[(parseInt(t1) >> 8) & 0xff] ^ this.TS2[t1 & 0xff];
        t2 = this.TX1[(parseInt(t2) >> 0x18) & 0xff] ^ this.TX2[(parseInt(t2) >> 0x10) & 0xff] ^ this.TS1[(parseInt(t2) >> 8) & 0xff] ^ this.TS2[t2 & 0xff];
        t3 = this.TX1[(parseInt(t3) >> 0x18) & 0xff] ^ this.TX2[(parseInt(t3) >> 0x10) & 0xff] ^ this.TS1[(parseInt(t3) >> 8) & 0xff] ^ this.TS2[t3 & 0xff];

        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        t3 = this.Badc(t3);
        t0 = this.Cdab(t0);
        t1 = this.Dcba(t1);

        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        t0 ^= w0[0];
        t1 ^= w0[1];
        t2 ^= w0[2];
        t3 ^= w0[3];

        w2[0] = t0;
        w2[1] = t1;
        w2[2] = t2;
        w2[3] = t3;

        q = (q == 2) ? 0 : (q + 1);

        t0 ^= this.KRK[q][0];
        t1 ^= this.KRK[q][1];
        t2 ^= this.KRK[q][2];
        t3 ^= this.KRK[q][3];

        t0 = this.TS1[(parseInt(t0) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t0) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t0) >> 8) & 0xff] ^ this.TX2[t0 & 0xff];
        t1 = this.TS1[(parseInt(t1) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t1) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t1) >> 8) & 0xff] ^ this.TX2[t1 & 0xff];
        t2 = this.TS1[(parseInt(t2) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t2) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t2) >> 8) & 0xff] ^ this.TX2[t2 & 0xff];
        t3 = this.TS1[(parseInt(t3) >> 0x18) & 0xff] ^ this.TS2[(parseInt(t3) >> 0x10) & 0xff] ^ this.TX1[(parseInt(t3) >> 8) & 0xff] ^ this.TX2[t3 & 0xff];

        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        t1 = this.Badc(t1);
        t2 = this.Cdab(t2);
        t3 = this.Dcba(t3);

        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        w3[0] = t0 ^ w1[0];
        w3[1] = t1 ^ w1[1];
        w3[2] = t2 ^ w1[2];
        w3[3] = t3 ^ w1[3];

        rk = this.Gsrk(w0, w1, 19, rk, j); j += 4;
        rk = this.Gsrk(w1, w2, 19, rk, j); j += 4;
        rk = this.Gsrk(w2, w3, 19, rk, j); j += 4;
        rk = this.Gsrk(w3, w0, 19, rk, j); j += 4;
        rk = this.Gsrk(w0, w1, 31, rk, j); j += 4;
        rk = this.Gsrk(w1, w2, 31, rk, j); j += 4;
        rk = this.Gsrk(w2, w3, 31, rk, j); j += 4;
        rk = this.Gsrk(w3, w0, 31, rk, j); j += 4;
        rk = this.Gsrk(w0, w1, 67, rk, j); j += 4;
        rk = this.Gsrk(w1, w2, 67, rk, j); j += 4;
        rk = this.Gsrk(w2, w3, 67, rk, j); j += 4;
        rk = this.Gsrk(w3, w0, 67, rk, j); j += 4;
        rk = this.Gsrk(w0, w1, 97, rk, j); j += 4;

        if (keyBits > 0x80)
        {
            rk = this.Gsrk(w1, w2, 97, rk, j); j += 4;
            rk = this.Gsrk(w2, w3, 97, rk, j); j += 4;
        }
        if (keyBits > 0xc0)
        {
            rk = this.Gsrk(w3, w0, 97, rk, j); j += 4;
            rk = this.Gsrk(w0, w1, 109, rk, j);
        }
        return rk;
    }
    ToInt(b0,b1,b2,b3) {
        return (b0 & 0xff) << 0x18 ^ (b1 & 0xff) << 0x10 ^ (b2 & 0xff) << 8 ^ b3 & 0xff;
    }
    ToByteArray( i, b, offset)
    {
        b[offset] = (parseInt(i) >> 0x18);
        b[offset + 1] = (parseInt(i) >> 0x10);
        b[offset + 2] = (parseInt(i) >> 8);
        b[offset + 3] = (parseInt(i));
    }
    Badc(t)
    {
        return ( System.Int64.clip32(System.Int64((t << 8)).and(System.Int64(4278255360))) ^ ((((((t >>> 0) >>> 8) & 16711935) >>> 0)) | 0));
    }

    Cdab(t)
    {
        return (System.Int64.clip32(((System.Int64((t << 16)).and(System.Int64(4294901760))))) ^ ((((((t >>> 0) >>> 16) & 65535) >>> 0)) | 0));
    }

    Dcba(t)
    {
        return ((t & 255) << 24 ^ (t & 65280) << 8 ^ (((((t & 16711680)) >>> 0) >>> 8) | 0) ^ ((System.Int64.clipu32((System.Int64(t).and(System.Int64(4278190080)))) >>> 24) | 0));
    }
    Gsrk( x, y, rot, rk, offset)
    {
        let q = 4 - parseInt(rot / 32);
        let r = rot % 32;
        let s = 32 - r;
        rk[offset] = (x[System.Array.index(0, x)] ^ (((((y[System.Array.index(q % 4, y)]) >>> 0) >>> r)) | 0)) ^ y[System.Array.index((((q + 3) | 0)) % 4, y)] << s;
        rk[offset + 1] = (x[System.Array.index(1, x)] ^ (((((y[System.Array.index((((q + 1) | 0)) % 4, y)]) >>> 0) >>> r)) | 0)) ^ y[System.Array.index(q % 4, y)] << s;
        rk[offset + 2] = (x[System.Array.index(2, x)] ^ (((((y[System.Array.index((((q + 2) | 0)) % 4, y)]) >>> 0) >>> r)) | 0)) ^ y[System.Array.index((((q + 1) | 0)) % 4, y)] << s;
        rk[offset + 3] = (x[System.Array.index(3, x)] ^ (((((y[System.Array.index((((q + 3) | 0)) % 4, y)]) >>> 0) >>> r)) | 0)) ^ y[System.Array.index((((q + 2) | 0)) % 4, y)] << s;
        return rk;
    }
    EncryptAriaAl( i, ioffset, o, ooffset)
    {
        if (this.keySizeAriaAlgorithm == 0)
        {
            alert("keySize");
        }
        if (this.encRoundKeys == null)
        {
            if (this.masterKeyAriaAlgorithm == null)
            {
                alert("masterKey");
            }
            else
            {
                this.SetupEncRoundKeys();
            }
        }
        this.DoCrypt(i, ioffset, this.encRoundKeys, this.numberOfRounds, o, ooffset);
    }
    EncryptAriaAlgorithm( i, ioffset)
    {
        let o = new Uint8Array(16);
        this.EncryptAriaAl(i, ioffset, o, 0);
        return o;
    }
    DecryptAriaAl( i, ioffset, o, ooffset)
    {
        if (this.keySizeAriaAlgorithm == 0)
        {
            alert("keySize");
        }
        if (this.decRoundKeys == null)
        {
            if (this.masterKeyAriaAlgorithm == null)
            {
                alert("masterKey");
            }
            else
            {
                this.SetupDecRoundKeys();
            }
        }
        this.DoCrypt(i, ioffset, this.decRoundKeys, this.numberOfRounds, o, ooffset);
    }
    DecryptAriaAlgorithm( i, ioffset)
    {
        let o = new Uint8Array(16);
        o = this.DecryptAriaAl(i, ioffset, o, 0);
        return o;
    }
    DoDecKeySetup( mk, rk, keyBits)
    {
        let a = 0;
        let z = 0;
        let t = new Int32Array(4);

        z = 32 + keyBits / 8;
        this.SwapBlocks(rk, 0, z);
        a += 4; z -= 4;

        for (; a < z; a += 4, z -= 4)
        {
            this.SwapAndDiffuse(rk, a, z, t);
        }
        this.Diff(rk, a, t, 0);

        rk[a] = t[0];
        rk[a + 1] = t[1];
        rk[a + 2] = t[2];
        rk[a + 3] = t[3];
        return rk;
    }
    Diff( i, offset1, o, offset2)
    {
        let t0, t1, t2, t3;

        t0 = this.M(i[offset1]);
        t1 = this.M(i[offset1 + 1]);
        t2 = this.M(i[offset1 + 2]);
        t3 = this.M(i[offset1 + 3]);
        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        t1 = this.Badc(t1);
        t2 = this.Cdab(t2);
        t3 = this.Dcba(t3);

        t1 ^= t2;
        t2 ^= t3;
        t0 ^= t1;
        t3 ^= t1;
        t2 ^= t0;
        t1 ^= t2;

        o[offset2] = t0;
        o[offset2 + 1] = t1;
        o[offset2 + 2] = t2;
        o[offset2 + 3] = t3;
    }
    M(t)
    {
        return parseInt(0x00010101 * ((parseInt(t) >> 0x18) & 0xff)) ^ parseInt(0x01000101 * ((parseInt(t) >> 0x10) & 0xff)) ^ parseInt(0x01010001 * ((parseInt(t) >> 8) & 0xff)) ^ 0x01010100 * (t & 0xff);
    }
    SwapAndDiffuse( arr, offset1, offset2, tmp)
    {
        this.Diff(arr, offset1, tmp, 0);
        this.Diff(arr, offset2, arr, offset1);
        arr[offset2] = tmp[0];
        arr[offset2 + 1] = tmp[1];
        arr[offset2 + 2] = tmp[2];
        arr[offset2 + 3] = tmp[3];
    }
    SwapBlocks( arr, offset1, offset2)
    {
        let t = 0;

        for (let i = 0; i < 4; i++)
        {
            t = arr[offset1 + i];
            arr[offset1 + i] = arr[offset2 + i];
            arr[offset2 + i] = t;
        }
    }
}
class AriaProvider extends AriaAlgorithm{

    constructor(){
        super(128);
        this.keySize = 128;
        this.plainTextBlock = null;
        this.cipherTextBlock = null;
        this.masterKey = null;    
    }
    SetDefaultMasterKey()
    {
        if (this.masterKey == null)
        {
            let length = (this.keySize == 128) ? 16 : ((this.keySize == 192) ? 24 : 32);
            this.masterKey = new Int32Array(length);
            for (let i = 0; i < length; i++)
            {
                this.masterKey[i] = i;
            }
        }
    }
    EncryptAriaPr(inputText)
    {
        this.SetDefaultMasterKey();

        this.SetKey(this.masterKey);
        this.SetupRoundKeys();

        this.EncryptDivisionBlock(inputText);
        this.EncryptAriaProvider();
    }
    EncryptAriaProvider()
    {
        this.cipherTextBlock = new Array(this.plainTextBlock.length);
        for (let i = 0; i < this.plainTextBlock.length; i++)
        {
            this.cipherTextBlock[i] = new Int32Array(16);
            this.EncryptAriaAl(this.plainTextBlock[i], 0, this.cipherTextBlock[i], 0);
        }
    }
    DecryptAriaPr(inputText)
    {
        this.SetDefaultMasterKey();
        
        this.SetKey(this.masterKey);
        this.SetupRoundKeys();

        this.DecryptDivisionBlock(inputText);
        this.DecryptAriaProvider();
    }
    DecryptAriaProvider()
    {
        this.plainTextBlock = new Array(this.cipherTextBlock.length);
        for (let i = 0; i < this.cipherTextBlock.length; i++)
        {
            this.plainTextBlock[i] = new Uint8Array(16);
            this.DecryptAriaAl(this.cipherTextBlock[i], 0, this.plainTextBlock[i], 0);
        }
    }
    
    ConvertToByteArrayFromString( str)
    {
        let temp = new Uint8Array(str.length);
        for (let i =0; i < str.length; i++) {
            temp[i] = str.charCodeAt(i);
        }

        if (temp.length % 16 != 0)
        {
           let temp2 = new Uint8Array(parseInt((temp.length / 16) + 1) * 16);
            this.BlockCopy(temp, 0, temp2, 0, temp.length);
            return temp2;
        }
        else
        {
            return temp;
        }
    }

    EncryptedConvertToByteArrayFromString( str)
    {
        let temp = new Uint8Array(str.length / 2);
        for (let i = 0; i < temp.length; i++)
        {
            let hex = str.substr(i * 2, 2);
            temp[i] = this.hexToDec(hex);
        }

        if (temp.length % 16 != 0)
        {
            let temp2 = new Uint8Array(((temp.length / 16) + 1) * 16);
            this.BlockCopy(temp, 0, temp2, 0, temp.length);
            return temp2;
        }
        else
        {
            return temp;
        }
    }

    EncryptDivisionBlock(plainText)
    {
        let temp = this.ConvertToByteArrayFromString(plainText);
        let index = parseInt((temp.length - 1) / 16 + 1);
        this.plainTextBlock = new Array(index);
        for (let i = 0, j = 0; j < index; i += 16, j++)
        {
            this.plainTextBlock[j] = new Uint8Array(16);
            this.BlockCopy(temp, i, this.plainTextBlock[j], 0, 16);
        }
    }
    DecryptDivisionBlock(cipherText)
    {
        let temp,index=0;
        if (!Array.isArray(cipherText)) {
            temp = this.EncryptedConvertToByteArrayFromString(cipherText);
            index = parseInt((temp.length - 1) / 16) + 1;
        } else {
            index = parseInt((cipherText.length - 1) / 16) + 1;
        }
        
        this.cipherTextBlock = new Array(index);
        for (let i = 0, j = 0; j < index; i += 16, j++)
        {
            this.cipherTextBlock[j] = new Uint8Array(16);
            this.BlockCopy(temp, i, this.cipherTextBlock[j], 0, 16);
        }
    }
    EncryptToString(inputString)
    {
        if (inputString == '' || inputString == null)
        {
            return inputString;
        }
        else
        {
            this.EncryptAriaPr(inputString);

            let sb = '';
            for (let i = 0; i < this.cipherTextBlock.length; i++)
            {
                for (let j = 0; j < this.cipherTextBlock[i].length; j++) {
                    sb += this.decToHex(this.cipherTextBlock[i][j]);
                }
            }
            return sb;
        }
    }
    EncryptToByteArray(inputString)
    {
        if (inputString == '' || inputString == null)
        {
            return inputString;
        }
        else
        {
            this.EncryptAriaPr(inputString);
            console.log(this.cipherTextBlock);
            let retValue = new Uint8Array(this.cipherTextBlock.length * 16);
            for (let i = 0, j = 0; i < this.cipherTextBlock.length; i++, j += 16)
            {
                this.BlockCopy(this.cipherTextBlock[i], 0, retValue, j, 16);
            }
            return retValue;
        }
    }
    DecryptFromString(inputString)
    {
        let dest = new Uint8Array(inputString.length);
        let resuft = '';
        if (inputString == '' || inputString == null)
        {
            return inputString;
        }
        else
        {
            this.DecryptAriaPr(inputString);

            for (let i = 0, j = 0; i < this.plainTextBlock.length; i++, j += 16)
            {
                this.BlockCopyDec(this.plainTextBlock[i], 0, dest, j, 16);
            }
            for (let n = 0; n< dest.length; n++) {
                resuft += String.fromCharCode(dest[n]);
            }
            return resuft.trim();
        }
    }
    DecryptFromByteArray(inputByteArray)
    {
        let dest = new Uint8Array(inputByteArray.length);
        let resuft = '';
        if (inputByteArray == null || inputByteArray == null)
        {
            return string.Empty;
        }
        else
        {
            this.DecryptAriaPr(inputByteArray);

            for (let i = 0, j = 0; i < this.plainTextBlock.length; i++, j += 16)
            {
                this.BlockCopyDec(this.plainTextBlock[i], 0, dest, j, 16);
            }
            for (let n = 0; n< dest.length; n++) {
                resuft += String.fromCharCode(dest[n]);
            }
            return resuft.trim();
        }
    }
    BlockCopyDec(src,srcOffset,dst,dstOffset,count){
        for (let i = srcOffset; i < count ; i++) {
            dst[dstOffset + i] = src[i];
        }
        return dst;
    }
    BlockCopy(src,srcOffset,dst,dstOffset,count){
        for (let i = dstOffset; i < count ; i++) {
            dst[i] = src[srcOffset + i];
        }
        return dst;
    }
    decToHex (src) {
        var mapping = {
            "0" : "0",
            "1" : "1",
            "2" : "2",
            "3" : "3",
            "4" : "4",
            "5" : "5",
            "6" : "6",
            "7" : "7",
            "8" : "8",
            "9" : "9",
            "10" : "A",
            "11" : "B",
            "12" : "C",
            "13" : "D",
            "14" : "E",
            "15" : "F"
        };
        var n = 0;
        var returnString = "";

        while (16 ** (n+1) < src) {
            var a = 16 ** (n+1);
            n++;
        }

        for (n; n >= 0; n--) {
            if (16 ** n <= src) {
                returnString += mapping[Math.floor(src / 16 ** n).toString()];
                src = src - Math.floor(src / 16 ** n) * (16 ** n);
            } else {
                returnString += "0";
            }
        }
        if (returnString.length < 2) {
            returnString = '0'+ returnString;
        }
        return returnString;
    }
    hexToDec (src) {
    
        var mapping = {
            "0" : "0",
            "1" : "1",
            "2" : "2",
            "3" : "3",
            "4" : "4",
            "5" : "5",
            "6" : "6",
            "7" : "7",
            "8" : "8",
            "9" : "9",
            "A" : "10",
            "B" : "11",
            "C" : "12",
            "D" : "13",
            "E" : "14",
            "F" : "15"
        };
        
        var srcString = src.toString();
        var returnNum = 0;
        var i;
        
        for (i = 0; i < srcString.length; i++) {
            returnNum += mapping[srcString[i]] * (16 ** (srcString.length -1 - i));
        }
        
        return returnNum;
        
    }
}