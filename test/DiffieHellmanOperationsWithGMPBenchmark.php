<?php
# This file benchmarks the operations needed to execute the Diffie Hellman algorithm.
# Using this GMP library is significantly faster than using BCMath.
# For example, to generate the public key:
#  0.845003128052 [ms] with GMP
#  vs
#  706.810188293 [ms] with BCMath

require_once('DiffieHellmanOperationsBenchmark.php');

function multipleOfTwo()
{
    return gmp_cmp(gmp_mod(OpenID_Association::Default_DH_Gen, '2'), '0') == 0;
}

function generatePublicKey()
{
    # private key: x
    # public key: y = g^x mod p
    global $privateKey;
    # gmp_powm($base, $exp, $mod)
    $y = gmp_powm(OpenID_Association::Default_DH_Gen, $privateKey, OpenID_Association::Default_DH_Modulus);
    return gmp_strval($y);
}

function generateSharedSecret()
{
    # shared secret: zz = ya^xb mod p
    global $otherPublicKey, $privateKey;
    $zz = gmp_powm($otherPublicKey, $privateKey, OpenID_Association::Default_DH_Modulus);
    return gmp_strval($zz);
}

if ($publicKey != generatePublicKey())
    die('Error: something is wrong with the public key generation.  The generated public key does not match the expected value.');
if ($sharedSecret != generateSharedSecret())
    die('Error: something is wrong with the shared secret generation.  The generated shared secret does not match the expected value.');

doBenchmark('multipleOfTwo');
doBenchmark('randomDecimal');
doBenchmark('generatePublicKey', 5);
doBenchmark('generateSharedSecret', 5);

?>
