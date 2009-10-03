<?php
# This file benchmarks the operations needed to execute the Diffie Hellman algorithm.
# Using this BCMatch library is significantly slower than using GMP.
# For example, to generate the public key:
#  0.845003128052 [ms] with GMP
#  vs
#  706.810188293 [ms] with BCMath

require_once('DiffieHellmanOperationsBenchmark.php');

function multipleOfTwo()
{
    return bccomp(bcmod(OpenID_Association::Default_DH_Gen, '2'), '0') == 0;
}

function generatePublicKey()
{
    # private key: x
    # public key: y = g^x mod p
    global $privateKey;
    $y = bcpowmod(OpenID_Association::Default_DH_Gen, $privateKey, OpenID_Association::Default_DH_Modulus);
    return $y;
}

function generateSharedSecret()
{
    # shared secret: zz = ya^xb mod p
    global $otherPublicKey, $privateKey;
    $zz = bcpowmod($otherPublicKey, $privateKey, OpenID_Association::Default_DH_Modulus);
    return $zz;
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
