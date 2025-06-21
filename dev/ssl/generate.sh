#!/bin/bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`
DOMAIN="lokilizer.local"
WILDCARD="*.$DOMAIN"
SSL_PATH="${SCRIPTPATH}/${DOMAIN}"
echo $SSL_PATH
PASSPHRASE=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
mkdir -p ${SSL_PATH}
cd ${SSL_PATH}

# https://stackoverflow.com/questions/7580508/getting-chrome-to-accept-self-signed-localhost-certificate/60516812#60516812
# Create CA-signed certs
echo $PASSPHRASE
openssl genrsa -aes128 -passout pass:${PASSPHRASE} -out LOKILIZER_CA.key 2048
# Generate root certificate
openssl req -subj "/C=RU/O=LOKILIZER/CN=LOKILIZER" -x509 -new -nodes -key LOKILIZER_CA.key -passin pass:${PASSPHRASE} -sha256 -days 825 -out LOKILIZER_CA.crt
# Generate a private key
openssl genrsa -out privkey.pem 2048
# Create a certificate-signing request
openssl req -subj "/C=RU/O=LOKILIZER/CN=LOKILIZER" -new -key privkey.pem -out fullchain.csr
# Create a config file for the extensions
>fullchain.ext cat <<-EOF
authorityKeyIdentifier=keyid,issuer
basicConstraints=CA:FALSE
keyUsage = digitalSignature, nonRepudiation, keyEncipherment, dataEncipherment
subjectAltName = @alt_names
[alt_names]
DNS.1 = ${DOMAIN}
DNS.2 = *.${DOMAIN}
DNS.3 = *.backend.${DOMAIN}
EOF

# Create the signed certificate
openssl x509 -req -in fullchain.csr -CA LOKILIZER_CA.crt -CAkey LOKILIZER_CA.key -passin pass:${PASSPHRASE} -CAcreateserial \
-out fullchain.pem -days 825 -sha256 -extfile fullchain.ext

rm -f LOKILIZER_CA.key
rm -f LOKILIZER_CA.srl
rm -f fullchain.ext
rm -f fullchain.csr